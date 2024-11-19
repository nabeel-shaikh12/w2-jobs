<?php

namespace App\Http\Controllers\User;

use App\Models\Portfolio;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Uploader;

class PortfolioController extends Controller
{
    use Uploader;
    public function store(Request $request)
    {

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'preview' => ['required', 'image'],
        ]);

        Portfolio::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'preview' => $this->saveFile($request, 'preview'),
        ]);

        return back();
    }

    public function destroy(Portfolio $portfolio)
    {
        $this->removeFile($portfolio->preview);
        $portfolio->delete();
        
        return back();
    }
}
