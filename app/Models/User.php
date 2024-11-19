<?php

namespace App\Models;

use App\Models\KycMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected static function boot()
    {
        parent::boot();
        // assign the username
        static::creating(function ($user) {
            $isNameUnique = User::where('name', $user->name)->count();
            $username = Str::of($user->name)
                ->trim()
                ->lower()
                ->replace(' ', '')
                ->append($isNameUnique > 0 ? $isNameUnique . Str::random(2) : '');

            $user->username = Str::slug($username);
        });
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'address',
        'avatar',
        'password',
        'role',
        'meta',
        'category_id',
        'status',
        'kyc_verified_at',
        'email_verified_at',
        'is_star',
        'credits',
        'provider',
        'provider_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'kyc_verified_at' => 'datetime',
        'status' => 'boolean',
        'is_star' => 'boolean',
        'meta' => 'json',
        'plan' => 'json',
    ];

    protected $appends = ['created_at_date'];

    public function getCreatedAtDateAttribute()
    {
        return $this->created_at?->format('d F Y');
    }

    public static function getpermissionGroups()
    {
        $permission_groups = DB::table('permissions')
            ->select('group_name as name')
            ->groupBy('group_name')
            ->get();
        return $permission_groups;
    }

    public static function getPermissionGroup()
    {
        return $permission_groups = DB::table('permissions')->select('group_name')->groupBy('group_name')->get();
    }
    public static function getpermissionsByGroupName($group_name)
    {
        $permissions = DB::table('permissions')
            ->select('name', 'id')
            ->where('group_name', $group_name)
            ->get();
        return $permissions;
    }

    public static function roleHasPermissions($role, $permissions)
    {
        $hasPermission = true;
        foreach ($permissions as $permission) {
            if (!$role->hasPermissionTo($permission->name)) {
                $hasPermission = false;
                return $hasPermission;
            }
        }
        return $hasPermission;
    }

    public function app()
    {
        return $this->hasOne('App\Models\App', 'user_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo('App\Models\Plan', 'plan_id');
    }

    public function subscription()
    {
        return $this->belongsTo('App\Models\Plan', 'plan_id');
    }

    public function orders()
    {
        return $this->hasMany('App\Models\Order');
    }

    public function smstransaction()
    {
        return $this->hasMany('App\Models\Smstransaction');
    }

    public function device()
    {
        return $this->hasMany('App\Models\Device');
    }

    public function contact()
    {
        return $this->hasMany('App\Models\Contact');
    }

    // relationship methods
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'userlocations', 'user_id', 'country_id');
    }

    public function states(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'userlocations', 'user_id', 'state_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'usercategories', 'user_id', 'category_id')->where('type', 'job_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'usercategories', 'user_id', 'category_id')->where('type', 'job_tag');
    }

    public function usercategories()
    {
        return $this->hasMany(Usercategory::class);
    }
    

    public function jobBookmarks(): BelongsToMany
    {
        return $this->belongsToMany(Opening::class, 'jobbookmarks', 'user_id', 'opening_id');
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function appliedJobs(): BelongsToMany
    {
        return $this->belongsToMany(Opening::class, 'userjobs', 'user_id', 'opening_id')->withTimestamps();
    }

    public function educationQualifications(): HasMany
    {
        return $this->hasMany(UserEducationQualification::class, 'user_id');
    }

    public function candidateBookmarks(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'candidatebookmarks', 'candidate_id', 'user_id');
    }

    public function employerBookmarks(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'candidatebookmarks', 'user_id', 'candidate_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Opening::class);
    }
    public function resume(): ?string
    {
        return $this->meta['resume'] ?? null;
    }
    public function openJobs(): HasMany
    {
        return $this->hasMany(Opening::class)->where('expired_at', '>', now());
    }

    public function kycMethods(): BelongsToMany
    {
        return $this->belongsToMany(KycMethod::class)->withPivot('kyc_request_id');
    }

    public function planmodel(): BelongsTo // users table has already 'plan' column
    {
        return $this->belongsTo('App\Models\Plan', 'plan_id');
    }

    public function profileVisits(): HasMany
    {
        return $this->hasMany(ProfileVisitor::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class);
    }

    public function candidateReviews(): HasMany
    {
        return $this->hasMany(CandidateReview::class, 'user_id');
    }

    public function companyReviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class, 'company_id');
    }

    //scopes
    public function scopeAdmins($query): Builder // Admins list
    {
        return $query->where('role', 'admin');
    }

    public function scopeCandidate($query): Builder //Candidates list
    {
        return $query->where('role', 'user');
    }

    public function scopeEmployer($query): Builder // Company list
    {
        return $query->where('role', 'employer');
    }

    public function scopeActive($query): Builder
    {
        return $query->where('status', 1);
    }

    public function scopeActiveCompany($query): Builder
    {
        $mustVerifyEmail = env('EMAIL_MUST_VERIFIED');
        $mustVerifyKyc = env('KYC_MUST_VERIFIED');

        // status active and employer role
        $query->active()->employer();

        // email verified on conditionally
        if ($mustVerifyEmail) {
            $query->whereNotNull('email_verified_at');
        }

        // kyc verified on conditionally
        if ($mustVerifyKyc) {
            $query->whereNotNull('kyc_verified_at');
        }

        

        return $query;
    }

    public function scopeActiveCandidate($query): Builder
    {
        // status == 1
        // category_id == not null
        return $query->active()->candidate()->whereNotNull('category_id');
    }

    public function scopeInActive($query): Builder
    {
        return $query->where('status', '!=', 1);
    }

    public function scopeVerified($query): Builder
    {
        return $query->where('kyc_verified_at', '!=', null);
    }

    // helper methods

    public function isAdmin(): bool
    {
        return $this->role == 'admin';
    }

    public function getDashboardRoute(): string // route name
    {
        return match ($this->role) {
            'admin' => 'admin.dashboard',
            'user' => 'user.dashboard',
            'employer' => 'employer.dashboard',
            default => 'home',
        };
    }

    public function completedProfileSetup(): bool
    {
        $stepsCompleted = ($this->meta['step_completed'] ?? 0);
        return $stepsCompleted >= 5;
    }

    public function profileCompletedPercent(): int
    {
        $result = 0;

        if ($this->role == 'employer') {
            $accountOpening = 10;
            $companyInformation = isset($this->meta['company']) && isset($this->meta['company']['name']) ? 30 : 0;
            $emailVerification = $this->email_verified_at ? 20 : 0;
            $kycVerification = $this->kyc_verified_at ? 30 : 0;
            $avatar = $this->avatar ? 10 : 0;

            $result = ($accountOpening +
                $companyInformation +
                $emailVerification +
                $kycVerification +
                $avatar
            );
        } elseif ($this->role == 'user') {
            $accountOpening = 10;
            $emailVerification = $this->email_verified_at ? 20 : 0;
            $profileSetup = $this->category_id ? 40 : 0;
            $avatar = $this->avatar ? 10 : 0;
            $cv = isset($this->meta['custom_cv']) ? 20 : 0;

            $result = ($accountOpening +
                $profileSetup +
                $emailVerification +
                $avatar +
                $cv
            );
        }

        return $result;
    }
    /**
     * Formate date for this model datetime attributes
     */
    public function formatedDateFor($attr = 'created_at', $format = 'd M, Y h:i a'): string
    {
        return $this->$attr ? now()->make($this->$attr)->format($format) : '';
    }

    // bookmarked template

    public function aiTemplates(): BelongsToMany
    {
        return $this->belongsToMany(AiTemplate::class);
    }

    public function aiGeneratedContents(): HasMany
    {
        return $this->hasMany(AiGenerate::class);
    }
}
