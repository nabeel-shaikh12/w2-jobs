<?php

namespace App\Actions;

use Illuminate\Http\Request;
use App\Models\Option;
use Str;
use Artisan;
use App\Traits\Uploader;
use Cache;

class Optionupdate
{
    use Uploader;


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function primaryDataUpdate(Request $request)
    {

        $primary_data = Option::where('key', 'primary_data')->first();
        $optionData = json_decode($primary_data->value);

        if (request()->hasFile('primary_data.logo')) {
            $newLogo = $this->saveFile(request(), 'primary_data.logo');
            $this->removeFile($optionData->logo);
            $optionData->logo = $newLogo;
        }

        if (request()->hasFile('primary_data.favicon')) {
            $favicon = $this->saveFile(request(), 'primary_data.favicon');
            $this->removeFile($optionData->favicon);
            $optionData->favicon = $favicon;
        }

        if (request()->hasFile('primary_data.deep_logo')) {
            $deep_logo = $this->saveFile(request(), 'primary_data.deep_logo');
            $this->removeFile($optionData->deep_logo ?? null);
            $optionData->deep_logo = $deep_logo;
        }

        $optionData->contact_email = $request->primary_data['contact_email'];
        $optionData->contact_phone = $request->primary_data['contact_phone'];
        $optionData->address       = $request->primary_data['address'];
        $optionData->socials       = $request->primary_data['socials'];

        $primary_data->value = json_encode($optionData);
        $primary_data->save();
    }

    public function themeUpdate(Request $request)
    {
        $theme = Option::where('key', 'theme_path')->first();

        if (empty($theme)) {
            $theme = new Option;
            $theme->key = 'theme_path';
        }
        $theme->value = $request->themeData;
        $theme->save();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function headerFooterUpdate(Request $request)
    {
        $locale = current_locale();

        $header_footer = Option::where('key', 'header_footer')->where('lang', $locale)->first();
        if (empty($header_footer)) {
            $request->validate([
                'header_footer.footer_button_image' => 'required|image|max:1024',
                'header_footer.footer_left_button_image' => 'required|image|max:1024',
            ]);

            $header_footer = new Option;
            $header_footer->key = 'header_footer';
            $header_footer->lang = $locale;

            $newData['header'] = $request->header_footer['header'];
            $newData['footer'] = $request->header_footer['footer'];



            $footer_button_image = $this->saveFile($request, 'header_footer.footer_button_image');
            $newData['footer_button_image'] = $footer_button_image;

            $footer_left_button_image = $this->saveFile($request, 'header_footer.footer_left_button_image');
            $newData['footer_left_button_image'] = $footer_left_button_image;
        } else {
            $request->validate([
                'header_footer.footer_button_image' => 'nullable|image|max:1024',
                'header_footer.footer_left_button_image' => 'nullable|image|max:1024',
            ]);

            $optionData = json_decode($header_footer->value ?? '');

            $newData['header'] = $request->header_footer['header'];
            $newData['footer'] = $request->header_footer['footer'];


            if ($request->hasFile('header_footer.footer_button_image')) {
                $footer_button_image = $this->saveFile($request, 'header_footer.footer_button_image');
                $this->removeFile($optionData->footer_button_image ?? null);
                $newData['footer_button_image'] = $footer_button_image;
            } else {
                $newData['footer_button_image'] = $optionData->footer_button_image ?? null;
            }

            if ($request->hasFile('header_footer.footer_left_button_image')) {
                $footer_left_button_image = $this->saveFile($request, 'header_footer.footer_left_button_image');
                $newData['footer_left_button_image'] = $footer_left_button_image;
            } else {
                $newData['footer_left_button_image'] = $optionData->footer_left_button_image ?? null;
            }
        }

        $header_footer->value = json_encode($newData);
        $header_footer->save();
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function contactPageUpdate(Request $request)
    {


        $contact = Option::where('key', 'contact-page')->first();
        if (empty($contact)) {
            $contact = new Option;
            $contact->key = 'contact-page';
        }

        $contact->value = json_encode($request->contact_page);
        $contact->save();
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function homePageUpdate(Request $request)
    {

        $locale = current_locale() ?? 'en';

        $option = Option::where('key', $request->theme)->where('lang', $locale)->first();
        $optionData = $request->home;

        if (empty($option)) {

          

            $option = new Option;
            $option->key = $request->theme;
            $option->lang = $locale;

            // ----------------hero Section---------------------//

            if ($request->hasFile('home.hero.image')) {
                $optionData['hero']['image'] = $this->saveFile($request, 'home.hero.image');
            }

            if ($request->hasFile('home.hero.image1')) {
                $optionData['hero']['image1'] = $this->saveFile($request, 'home.hero.image1');
            }

            if ($request->hasFile('home.hero.image2')) {
                $optionData['hero']['image2'] = $this->saveFile($request, 'home.hero.image2');
            }

            if ($request->hasFile('home.hero.image3')) {
                $optionData['hero']['image3'] = $this->saveFile($request, 'home.hero.image3');
            }

            if ($request->hasFile('home.hero.image4')) {
                $optionData['hero']['image4'] = $this->saveFile($request, 'home.hero.image4');
            }
            if ($request->hasFile('home.hero.image5')) {
                $optionData['hero']['image5'] = $this->saveFile($request, 'home.hero.image5');
            }
            if ($request->hasFile('home.hero.image6')) {
                $optionData['hero']['image6'] = $this->saveFile($request, 'home.hero.image6');
            }

            // ----------------Call to action 1---------------------//
            if ($request->hasFile('home.cta_s1.image')) {
                $optionData['cta_s1']['image'] = $this->saveFile($request, 'home.cta_s1.image');
            }
            if ($request->hasFile('home.cta_s1.image1')) {
                $optionData['cta_s1']['image1'] = $this->saveFile($request, 'home.cta_s1.image1');
            }
            if ($request->hasFile('home.cta_s1.image2')) {
                $optionData['cta_s1']['image2'] = $this->saveFile($request, 'home.cta_s1.image2');
            }
            if ($request->hasFile('home.cta_s1.image3')) {
                $optionData['cta_s1']['image3'] = $this->saveFile($request, 'home.cta_s1.image3');
            }
            if ($request->hasFile('home.cta_s1.image4')) {
                $optionData['cta_s1']['image4'] = $this->saveFile($request, 'home.cta_s1.image4');
            }
            if ($request->hasFile('home.cta_s1.image5')) {
                $optionData['cta_s1']['image5'] = $this->saveFile($request, 'home.cta_s1.image5');
            }
            if ($request->hasFile('home.cta_s1.image6')) {
                $optionData['cta_s1']['image6'] = $this->saveFile($request, 'home.cta_s1.image6');
            }

            // ----------------Call to action 2---------------------//
            if ($request->hasFile('home.cta_s2.image')) {
                $optionData['cta_s2']['image'] = $this->saveFile($request, 'home.cta_s2.image');
            }
            if ($request->hasFile('home.cta_s2.image1')) {
                $optionData['cta_s2']['image1'] = $this->saveFile($request, 'home.cta_s2.image1');
            }
            if ($request->hasFile('home.cta_s2.image2')) {
                $optionData['cta_s2']['image2'] = $this->saveFile($request, 'home.cta_s2.image2');
            }
            if ($request->hasFile('home.cta_s2.image3')) {
                $optionData['cta_s2']['image3'] = $this->saveFile($request, 'home.cta_s2.image3');
            }
            if ($request->hasFile('home.cta_s2.image4')) {
                $optionData['cta_s2']['image4'] = $this->saveFile($request, 'home.cta_s2.image4');
            }
            if ($request->hasFile('home.cta_s2.image5')) {
                $optionData['cta_s2']['image5'] = $this->saveFile($request, 'home.cta_s2.image5');
            }
            if ($request->hasFile('home.cta_s2.image6')) {
                $optionData['cta_s2']['image6'] = $this->saveFile($request, 'home.cta_s2.image6');
            }

            // ----------------Call to action 3---------------------//
            if ($request->hasFile('home.cta_s3.image')) {
                $optionData['cta_s3']['image'] = $this->saveFile($request, 'home.cta_s3.image');
            }

            if ($request->hasFile('home.cta_s3.image1')) {
                $optionData['cta_s3']['image1'] = $this->saveFile($request, 'home.cta_s3.image1');
            }
            if ($request->hasFile('home.cta_s3.image2')) {
                $optionData['cta_s3']['image2'] = $this->saveFile($request, 'home.cta_s3.image2');
            }
            if ($request->hasFile('home.cta_s3.image3')) {
                $optionData['cta_s3']['image3'] = $this->saveFile($request, 'home.cta_s3.image3');
            }
            if ($request->hasFile('home.cta_s3.image4')) {
                $optionData['cta_s3']['image4'] = $this->saveFile($request, 'home.cta_s3.image4');
            }
            if ($request->hasFile('home.cta_s3.image5')) {
                $optionData['cta_s3']['image5'] = $this->saveFile($request, 'home.cta_s3.image5');
            }
            if ($request->hasFile('home.cta_s3.image6')) {
                $optionData['cta_s3']['image6'] = $this->saveFile($request, 'home.cta_s3.image6');
            }

            // ----------------Call to action 4---------------------//
            if ($request->hasFile('home.cta_s4.image')) {
                $optionData['cta_s4']['image'] = $this->saveFile($request, 'home.cta_s4.image');
            }

            if ($request->hasFile('home.cta_s4.image1')) {
                $optionData['cta_s4']['image1'] = $this->saveFile($request, 'home.cta_s4.image1');
            }
            if ($request->hasFile('home.cta_s4.image2')) {
                $optionData['cta_s4']['image2'] = $this->saveFile($request, 'home.cta_s4.image2');
            }
            if ($request->hasFile('home.cta_s4.image3')) {
                $optionData['cta_s4']['image3'] = $this->saveFile($request, 'home.cta_s4.image3');
            }
            if ($request->hasFile('home.cta_s4.image4')) {
                $optionData['cta_s4']['image4'] = $this->saveFile($request, 'home.cta_s4.image4');
            }
            if ($request->hasFile('home.cta_s4.image5')) {
                $optionData['cta_s4']['image5'] = $this->saveFile($request, 'home.cta_s4.image5');
            }
            if ($request->hasFile('home.cta_s4.image6')) {
                $optionData['cta_s4']['image6'] = $this->saveFile($request, 'home.cta_s4.image6');
            }
            // feedback section
            if ($request->hasFile('home.feedback_section.image')) {
                $optionData['feedback_section']['image'] = $this->saveFile($request, 'home.feedback_section.image');
            }
            
            if ($request->hasFile('home.feedback_section.image1')) {
                $optionData['feedback_section']['image1'] = $this->saveFile($request, 'home.feedback_section.image1');
            }

            $data = json_encode($optionData);
            $option->value = $data;
            $option->save();
        } else {

            $oldData = json_decode($option->value ?? '');

            // hero Section
            if ($request->hasFile('home.hero.image')) {
                $optionData['hero']['image'] = $this->saveFile($request, 'home.hero.image');
            } else {
                $optionData['hero']['image'] = $oldData->hero?->image ?? null;
            }

            if ($request->hasFile('home.hero.image1')) {
                $optionData['hero']['image1'] = $this->saveFile($request, 'home.hero.image1');
            } else {
                $optionData['hero']['image1'] = $oldData->hero?->image1 ?? null;
            }

            if ($request->hasFile('home.hero.image2')) {
                $optionData['hero']['image2'] = $this->saveFile($request, 'home.hero.image2');
            } else {
                $optionData['hero']['image2'] = $oldData->hero?->image2 ?? null;
            }
            if ($request->hasFile('home.hero.image3')) {
                $optionData['hero']['image3'] = $this->saveFile($request, 'home.hero.image3');
            } else {
                $optionData['hero']['image3'] = $oldData->hero?->image3 ?? null;
            }
            if ($request->hasFile('home.hero.image4')) {
                $optionData['hero']['image4'] = $this->saveFile($request, 'home.hero.image4');
            } else {
                $optionData['hero']['image4'] = $oldData->hero?->image4 ?? null;
            }
            if ($request->hasFile('home.hero.image5')) {
                $optionData['hero']['image5'] = $this->saveFile($request, 'home.hero.image5');
            } else {
                $optionData['hero']['image5'] = $oldData->hero?->image5 ?? null;
            }
            if ($request->hasFile('home.hero.image6')) {
                $optionData['hero']['image6'] = $this->saveFile($request, 'home.hero.image6');
            } else {
                $optionData['hero']['image6'] = $oldData->hero?->image6 ?? null;
            }


            // call to action  1
            if ($request->hasFile('home.cta_s1.image')) {
                $optionData['cta_s1']['image'] = $this->saveFile($request, 'home.cta_s1.image');
            } else {
                $optionData['cta_s1']['image'] = $oldData->cta_s1?->image ?? null;
            }
            if ($request->hasFile('home.cta_s1.image1')) {
                $optionData['cta_s1']['image1'] = $this->saveFile($request, 'home.cta_s1.image1');
            } else {
                $optionData['cta_s1']['image1'] = $oldData->cta_s1?->image1 ?? null;
            }

            if ($request->hasFile('home.cta_s1.image2')) {
                $optionData['cta_s1']['image2'] = $this->saveFile($request, 'home.cta_s1.image2');
            } else {
                $optionData['cta_s1']['image2'] = $oldData->cta_s1?->image2 ?? null;
            }

            if ($request->hasFile('home.cta_s1.image3')) {
                $optionData['cta_s1']['image3'] = $this->saveFile($request, 'home.cta_s1.image3');
            } else {
                $optionData['cta_s1']['image3'] = $oldData->cta_s1?->image3 ?? null;
            }

            if ($request->hasFile('home.cta_s1.image4')) {
                $optionData['cta_s1']['image4'] = $this->saveFile($request, 'home.cta_s1.image4');
            } else {
                $optionData['cta_s1']['image4'] = $oldData->cta_s1?->image4 ?? null;
            }

            if ($request->hasFile('home.cta_s1.image5')) {
                $optionData['cta_s1']['image5'] = $this->saveFile($request, 'home.cta_s1.image5');
            } else {
                $optionData['cta_s1']['image5'] = $oldData->cta_s1?->image5 ?? null;
            }

            if ($request->hasFile('home.cta_s1.image6')) {
                $optionData['cta_s1']['image6'] = $this->saveFile($request, 'home.cta_s1.image6');
            } else {
                $optionData['cta_s1']['image6'] = $oldData->cta_s1?->image6 ?? null;
            }

            // call to action  2
            if ($request->hasFile('home.cta_s2.image')) {
                $optionData['cta_s2']['image'] = $this->saveFile($request, 'home.cta_s2.image');
            } else {
                $optionData['cta_s2']['image'] = $oldData->cta_s2?->image ?? null;
            }
            if ($request->hasFile('home.cta_s2.image1')) {
                $optionData['cta_s2']['image1'] = $this->saveFile($request, 'home.cta_s2.image1');
            } else {
                $optionData['cta_s2']['image1'] = $oldData->cta_s2?->image1 ?? null;
            }

            if ($request->hasFile('home.cta_s2.image2')) {
                $optionData['cta_s2']['image2'] = $this->saveFile($request, 'home.cta_s2.image2');
            } else {
                $optionData['cta_s2']['image2'] = $oldData->cta_s2?->image2 ?? null;
            }

            if ($request->hasFile('home.cta_s2.image3')) {
                $optionData['cta_s2']['image3'] = $this->saveFile($request, 'home.cta_s2.image3');
            } else {
                $optionData['cta_s2']['image3'] = $oldData->cta_s2?->image3 ?? null;
            }

            if ($request->hasFile('home.cta_s2.image4')) {
                $optionData['cta_s2']['image4'] = $this->saveFile($request, 'home.cta_s2.image4');
            } else {
                $optionData['cta_s2']['image4'] = $oldData->cta_s2?->image4 ?? null;
            }

            if ($request->hasFile('home.cta_s2.image5')) {
                $optionData['cta_s2']['image5'] = $this->saveFile($request, 'home.cta_s2.image5');
            } else {
                $optionData['cta_s2']['image5'] = $oldData->cta_s2?->image5 ?? null;
            }

            if ($request->hasFile('home.cta_s2.image6')) {
                $optionData['cta_s2']['image6'] = $this->saveFile($request, 'home.cta_s2.image6');
            } else {
                $optionData['cta_s2']['image6'] = $oldData->cta_s2?->image6 ?? null;
            }

            // call to action 3
            if ($request->hasFile('home.cta_s3.image')) {
                $optionData['cta_s3']['image'] = $this->saveFile($request, 'home.cta_s3.image');
            } else {
                $optionData['cta_s3']['image'] = $oldData->cta_s3?->image ?? null;
            }

            if ($request->hasFile('home.cta_s3.image1')) {
                $optionData['cta_s3']['image1'] = $this->saveFile($request, 'home.cta_s3.image1');
            } else {
                $optionData['cta_s3']['image1'] = $oldData->cta_s3?->image1 ?? null;
            }

            if ($request->hasFile('home.cta_s3.image2')) {
                $optionData['cta_s3']['image2'] = $this->saveFile($request, 'home.cta_s3.image2');
            } else {
                $optionData['cta_s3']['image2'] = $oldData->cta_s3?->image2 ?? null;
            }

            if ($request->hasFile('home.cta_s3.image3')) {
                $optionData['cta_s3']['image3'] = $this->saveFile($request, 'home.cta_s3.image3');
            } else {
                $optionData['cta_s3']['image3'] = $oldData->cta_s3?->image3 ?? null;
            }

            if ($request->hasFile('home.cta_s3.image4')) {
                $optionData['cta_s3']['image4'] = $this->saveFile($request, 'home.cta_s3.image4');
            } else {
                $optionData['cta_s3']['image4'] = $oldData->cta_s3?->image4 ?? null;
            }

            if ($request->hasFile('home.cta_s3.image5')) {
                $optionData['cta_s3']['image5'] = $this->saveFile($request, 'home.cta_s3.image5');
            } else {
                $optionData['cta_s3']['image5'] = $oldData->cta_s3?->image5 ?? null;
            }

            if ($request->hasFile('home.cta_s3.image6')) {
                $optionData['cta_s3']['image6'] = $this->saveFile($request, 'home.cta_s3.image6');
            } else {
                $optionData['cta_s3']['image6'] = $oldData->cta_s3?->image6 ?? null;
            }

            // call to action 4
            if ($request->hasFile('home.cta_s4.image')) {
                $optionData['cta_s4']['image'] = $this->saveFile($request, 'home.cta_s4.image');
            } else {
                $optionData['cta_s4']['image'] = $oldData->cta_s4?->image ?? null;
            }

            if ($request->hasFile('home.cta_s4.image1')) {
                $optionData['cta_s4']['image1'] = $this->saveFile($request, 'home.cta_s4.image1');
            } else {
                $optionData['cta_s4']['image1'] = $oldData->cta_s4?->image1 ?? null;
            }

            if ($request->hasFile('home.cta_s4.image2')) {
                $optionData['cta_s4']['image2'] = $this->saveFile($request, 'home.cta_s4.image2');
            } else {
                $optionData['cta_s4']['image2'] = $oldData->cta_s4?->image2 ?? null;
            }

            if ($request->hasFile('home.cta_s4.image3')) {
                $optionData['cta_s4']['image3'] = $this->saveFile($request, 'home.cta_s4.image3');
            } else {
                $optionData['cta_s4']['image3'] = $oldData->cta_s4?->image3 ?? null;
            }

            if ($request->hasFile('home.cta_s4.image4')) {
                $optionData['cta_s4']['image4'] = $this->saveFile($request, 'home.cta_s4.image4');
            } else {
                $optionData['cta_s4']['image4'] = $oldData->cta_s4?->image4 ?? null;
            }

            if ($request->hasFile('home.cta_s4.image5')) {
                $optionData['cta_s4']['image5'] = $this->saveFile($request, 'home.cta_s4.image5');
            } else {
                $optionData['cta_s4']['image5'] = $oldData->cta_s4?->image5 ?? null;
            }

            if ($request->hasFile('home.cta_s4.image6')) {
                $optionData['cta_s4']['image6'] = $this->saveFile($request, 'home.cta_s4.image6');
            } else {
                $optionData['cta_s4']['image6'] = $oldData->cta_s4?->image6 ?? null;
            }


            // Feedback Section
            if ($request->hasFile('home.feedback_section.image')) {
                $optionData['feedback_section']['image'] = $this->saveFile($request, 'home.feedback_section.image');
            } else {
                $optionData['feedback_section']['image'] = $oldData->feedback_section?->image ?? null;
            }
            if ($request->hasFile('home.feedback_section.image1')) {
                $optionData['feedback_section']['image1'] = $this->saveFile($request, 'home.feedback_section.image1');
            } else {
                $optionData['feedback_section']['image1'] = $oldData->feedback_section?->image1 ?? null;
            }


            $data = json_encode($optionData);
            $option->value = $data;
            $option->save();
        }
    }
}
