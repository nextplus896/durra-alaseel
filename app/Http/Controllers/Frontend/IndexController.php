<?php

namespace App\Http\Controllers\Frontend;

use App\Constants\LanguageConst;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Admin\Language;
use App\Models\Admin\UsefulLink;
use App\Models\Admin\SiteSections;
use App\Models\Frontend\Subscribe;
use App\Constants\SiteSectionConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\Admin\AdminNotification;
use App\Models\Admin\AppSettings;
use App\Models\Admin\Cars\CarArea;
use App\Models\Admin\Cars\CarType;
use App\Models\Admin\InvestmentPlan;
use App\Models\Admin\SetupPage;
use App\Models\Frontend\Announcement;
use App\Models\Frontend\AnnouncementCategory;
use App\Models\Frontend\ContactRequest;
use App\Models\Vendor\Cars\Car;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(BasicSettingsProvider $basic_settings)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = $basic_settings->get()?->site_name . " | " . $basic_settings->get()?->site_title;

        $page_section       = SetupPage::where('slug','home')->with(['sections' => function($q){
            $q->where('status',true);
        }])->first();


        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();


        $cars = Car::where('status', true)
            ->where('approval', true)
            ->whereHas('type', function ($query) {
                $query->where('status', true);
            })
            ->whereHas('branch', function ($query) {
                $query->where('status', true);
            })
            ->where(function ($query) {
                $query->whereHas('bookings', function ($subquery) {
                    $subquery->where('status', '=', 3)->orWhere('status', '=', 1);
                })->orWhereDoesntHave('bookings');
            })
            ->get();
        $areas = CarArea::where('status', true)->get();
        $types = CarType::where('status', true)->get();

        return view('frontend.pages.index', compact(
            'site_name',
            'page_title',
            'page_section',
            'footer',
        ));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function findCar(BasicSettingsProvider $basic_settings)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = setPageTitle(__("Find Car"));
        $default = LanguageConst::NOT_REMOVABLE;

        $page_section       = SetupPage::where('slug','find-car')->with(['sections' => function($q){
            $q->where('status',true);
        }])->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        return view('frontend.pages.find-car', compact(
            'site_name',
            'page_title',
            'page_section',
            'footer',
            'default'
        ));

        return view('frontend.pages.find-car', compact('site_name', 'page_title', 'footer'));
    }

    public function cars(BasicSettingsProvider $basic_settings)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = setPageTitle(__("Cars"));
        $default = LanguageConst::NOT_REMOVABLE;
        $cars = session('cars');
        $token = session('token');
        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        return view('frontend.pages.cars', compact(
            'site_name',
            'page_title',
            "token",
            "cars",
            'footer',
            'default'
        ));
    }

    public function searchCar(BasicSettingsProvider $basic_settings, Request $request)
    {
        $page_title = setPageTitle(__("Cars"));

        $validator = Validator::make($request->all(), [
            'area'   => 'nullable',
            'type'   => 'nullable',
        ]);
        if ($validator->fails()) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }
        if ($request->area && $request->type) {

            $cars = Car::where('car_area_id', $request->area)
                ->where('car_type_id', $request->type)
                ->where('status', true)
                ->where('approval', true)
                ->where(function ($query) {
                    $query->whereHas('bookings', function ($subquery) {
                        $subquery->where('status', '=', 3)->orWhere('status', '=', 1)->orWhere('status', '=', 4);
                    })->orWhereDoesntHave('bookings');
                })
                ->get();
        } else {
            $cars = Car::where('status', true)
                ->where('approval', true)
                ->whereHas('type', function ($query) {
                    $query->where('status', true);
                })
                ->whereHas('branch', function ($query) {
                    $query->where('status', true);
                })
                ->where(function ($query) {
                    $query->whereHas('bookings', function ($subquery) {
                        $subquery->where('status', '=', 3)->orWhere('status', '=', 1)->orWhere('status', '=', 4);
                    })->orWhereDoesntHave('bookings');
                })
                ->get();
        }
        $site_name = $basic_settings->get()?->site_name;
        $areas = CarArea::where('status', true)->get();
        $searchArea = $request->area;
        $searchType = $request->type;
        $app_slug = Str::slug(SiteSectionConst::APP_SECTION);
        $app = SiteSections::getData($app_slug)->first();
        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        return view('frontend.pages.find-car', compact(
            'site_name',
            'page_title',
            'cars',
            'searchArea',
            'searchType',
            'areas',
            'app',
            'footer',
        ));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function vendor(BasicSettingsProvider $basic_settings)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = $basic_settings->get()?->site_name . " | " . $basic_settings->get()?->site_title;
        $basic_setting = $basic_settings->get();

        $page_section       = SetupPage::where('slug','vendor')->with(['sections' => function($q){
            $q->where('status',true);
        }])->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        return view('frontend.pages.vendor', compact(
            'site_name',
            'page_title',
            'basic_setting',
            'page_section',
            'footer'
        ));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function aboutUs(BasicSettingsProvider $basic_settings)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = $basic_settings->get()?->site_name . " | " . $basic_settings->get()?->site_title;

        $page_section       = SetupPage::where('slug','about-us')->with(['sections' => function($q){
            $q->where('status',true);
        }])->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        return view('frontend.pages.about-us', compact(
            'site_name',
            'page_title',
            'page_section',
            'footer'
        ));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function services(BasicSettingsProvider $basic_settings)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = $basic_settings->get()?->site_name . " | " . $basic_settings->get()?->site_title;

        $page_section       = SetupPage::where('slug','services')->with(['sections' => function($q){
            $q->where('status',true);
        }])->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        return view('frontend.pages.services', compact(
            'site_name',
            'page_title',
            'page_section',
            'footer'
        ));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function blog(BasicSettingsProvider $basic_settings)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = $basic_settings->get()?->site_name . " | " . $basic_settings->get()?->site_title;

        $page_section       = SetupPage::where('slug','blog')->with(['sections' => function($q){
            $q->where('status',true);
        }])->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();
        return view('frontend.pages.blog', compact(
            'site_name',
            'page_title',
            'page_section',
            'footer'
        ));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function blogDetail(BasicSettingsProvider $basic_settings, $id)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = $basic_settings->get()?->site_name . " | " . $basic_settings->get()?->site_title;
        $blog_categories = AnnouncementCategory::get();
        $blog = Announcement::where('id', $id)->first();
        $recent_blogs = Announcement::latest()->get();
        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();

        return view('frontend.pages.blog-details', compact('site_name', 'page_title', 'blog_categories', 'blog', 'recent_blogs', 'footer'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function categoryBlog(BasicSettingsProvider $basic_settings, $id)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = $basic_settings->get()?->site_name . " | " . $basic_settings->get()?->site_title;

        $blog_categories = AnnouncementCategory::get();
        $blogs = Announcement::where('announcement_category_id', $id)->latest()->get();
        $recent_blogs = Announcement::latest()->get();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();

        return view('frontend.pages.category-blog', compact('site_name', 'page_title', 'blog_categories', 'blogs', 'recent_blogs', 'footer'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function contact(BasicSettingsProvider $basic_settings)
    {
        $site_name = $basic_settings->get()?->site_name;
        $page_title = $basic_settings->get()?->site_name . " | " . $basic_settings->get()?->site_title;

        $page_section       = SetupPage::where('slug','contact')->with(['sections' => function($q){
            $q->where('status',true);
        }])->first();

        $footer_slug = Str::slug(SiteSectionConst::FOOTER_SECTION);
        $footer = SiteSections::getData($footer_slug)->first();

        return view('frontend.pages.contact', compact(
            'site_name',
            'page_title',
            'page_section',
            'footer'
        ));
    }


    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => "required|string|email|max:255|unique:subscribes",
        ]);

        if ($validator->fails()) return redirect('/#subscribe-form')->withErrors($validator)->withInput();

        $validated = $validator->validate();
        try {
            Subscribe::create([
                'email'         => $validated['email'],
                'created_at'    => now(),
            ]);
        } catch (Exception $e) {
            return redirect('/#subscribe-form')->with(['error' => ['Failed to subscribe. Try again']]);
        }

        return redirect(url()->previous() . '/#subscribe-form')->with(['success' => ['Subscription successful!']]);
    }

    public function contactMessageSend(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name'      => "required|string|max:255",
            'email'     => "required|email|string|max:255",
            'message'   => "required|string|max:5000",
        ])->validate();
        try {
            ContactRequest::create($validated);
        } catch (Exception $e) {
            return back()->with(['error' => ['Failed to send message. Please Try again']]);
        }

        return back()->with(['success' => ['Message send successfully!']]);
    }

    public function usefulLink($slug)
    {
        $useful_link = UsefulLink::where("slug", $slug)->first();
        if (!$useful_link) abort(404);

        $basic_settings = BasicSettingsProvider::get();

        $app_local = get_default_language_code();
        $page_title = $useful_link->title?->language?->$app_local?->title ?? $basic_settings->site_name;

    }


    public function languageSwitch(Request $request)
    {
        $code = $request->target;
        $language = Language::where("code", $code)->first();
        if (!$language) {
            return back()->with(['error' => ['Oops! Language Not Found!']]);
        }
        Session::put('local', $code);
        Session::put('local_dir', $language->dir);

        return back()->with(['success' => ['Language Switch to ' . $language->name]]);
    }

    public function subscribersStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validate();

        $validated['created_at'] = now();
        $validated['reply'] = 0;
        try {
            $message = Subscribe::create($validated);
            $notification_content = [
                'title'         => "Subscriber",
                'message'       => __("A User Has subscribed!"),
                'email'         => $validated['email'],
            ];
            AdminNotification::create([
                'admin_id' => 1,
                'type'     => "SIDE_NAV",
                'message'   => $notification_content,
            ]);
        } catch (Exception $e) {
            return back()->withErrors($validator)->withInput()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Subscribed Successfully!')]]);
    }

    public function getAreaTypes(Request $request)
    {
        $validator    = Validator::make($request->all(), [
            'area'  => 'required|integer',
        ]);
        if ($validator->fails()) {
            return Response::error($validator->errors()->all());
        }
        $area = CarArea::with(['types' => function ($type) {
            $type->with(['type' => function ($car_type) {
                $car_type->where('status', true);
            }]);
        }])->find($request->area);
        if (!$area) return Response::error([__('Area Not Found')], 404);

        return Response::success([__('Data fetch successfully')], ['area' => $area], 200);
    }
}
