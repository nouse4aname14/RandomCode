<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/


Route::get('/analytics/__ssa.gif', array("as" => 'analytics.track',"after"=>"cookie.remove", 'uses' => 'AnalyticController@getTrack'));

Route::get('/logout',  array('before'=> 'force.ssl', 'as' => 'logout',      'uses' => 'AuthController@getLogout'));
Route::get('/login',   array('before'=> 'force.ssl', 'as' => 'login',       'uses' => 'AuthController@getLogin'));
Route::post('/login',  array('before'=> 'force.ssl', 'as' => 'login.post',  'uses' => 'AuthController@postLogin'));

Route::get('/forgotten-password',   array('before'=> 'force.ssl','as' => 'auth.get.forgot',       'uses' => 'AuthController@getForgot'));
Route::get('/forgotten-password/{reset}',   array('before'=> 'force.ssl','as' => 'auth.get.forgot.reset',       'uses' => 'AuthController@getForgotReset'));
Route::post('/forgotten-password/{reset}',   array('before'=> 'force.ssl','as' => 'auth.post.forgot.reset',       'uses' => 'AuthController@postForgotReset'));
Route::post('/forgotten-password',   array('before'=> 'force.ssl','as' => 'auth.post.forgot',       'uses' => 'AuthController@postForgot'));

Route::get("/sample/{site}", array("as" => "sample.index", "after"=>"cookie.remove", "uses" => "SampleController@getIndex"));

Route::any("/gallery/facebook", array("as" => "gallery.facebook", "after"=>"cookie.remove", "uses" => "GalleryController@showFacebookGallery"));

Route::get("/gallery/{campaign_id}/{widget_id}", array("as" => "gallery.index.preview", "after"=>"cookie.remove", "uses" => "GalleryController@showGallery"));

Route::get("/gallery", array("as" => "gallery.index", "after"=>"cookie.remove", "uses" => "GalleryController@showGallery"));
Route::get("/v1/feed/{campaign_id}/{public_uid}.{extension}", array("as" => 'widgetdist.get.feed', "after"=>"cookie.remove",'uses' => 'WidgetDistributionController@getFeed'));

//Deprecated Redirects
Route::get("/gallery/{widget_id}", array("as" => "gallery.index.redirect","after"=>"cookie.remove",  "uses" => "GalleryController@showGalleryRedirect"));
Route::get("/v1/feed/{public_uid}.{extension}", array("as" => 'widgetdist.get.feed.redirect',"after"=>"cookie.remove", 'uses' => 'WidgetDistributionController@getFeedRedirect'));


Route::group(array('prefix' => 'widget'), function()
{
    Route::get('/{campaign_id}/{public_uid}', array("as" => 'widgetdist.get.unique', 'uses' => 'WidgetDistributionController@getUniqueWidget'));
    Route::get('/upload', array("as" => 'widgetdist.get.upload', 'uses' => 'WidgetDistributionController@getUpload'));

    //Deprecated Routes
    Route::get('/{public_uid}', array("as" => 'widgetdist.get.unique.redirect', 'uses' => 'WidgetDistributionController@getUniqueWidgetRedirect'));
    Route::get('/{public_uid}/{type}', array("as" => 'widgetdist.get', 'uses' => 'WidgetDistributionController@getWidget'));

});

Route::group(array('prefix' => 'x-upload'), function()
{
    Route::get('/{public_uid}', array("as" => 'x-upload.show',"after"=>"cookie.remove", 'uses' => 'RemoteUploadController@getUploadShow'));
    Route::post('/{public_uid}', array("as" => 'x-upload.post',"after"=>"cookie.remove", 'uses' => 'RemoteUploadController@postUpload'));
});

Route::group(array('prefix' => 'dashboard', 'before' => 'auth.dashboard|force.ssl'), function()
{

    /**
     * Normal User Dashboard Routes
     *
     */
    Route::any('/',         array("as" => "dashboard.home", "before"=>"campaign.exists",   "uses" => "DashboardController@getHome"));
    Route::any('/queue',         array("as" => "dashboard.index", "before"=>"campaign.exists",   "uses" => "DashboardController@getIndex"));
    Route::any('/site/{site}',         array("as" => "dashboard.siteswitch", "before"=>"campaign.exists",   "uses" => "DashboardController@getSiteSwitch"));
    Route::get('/sites', array("as" => "dashboard.sites", "before"=>"campaign.exists",   "uses" => "DashboardController@getSites"));
    Route::get('/profile', array("as" => "dashboard.profile",   "uses" => "DashboardController@getProfile"));
    Route::post('/profile', array("as" => "dashboard.profile.post",   "uses" => "DashboardController@postProfile"));
    Route::post('/media/approve', array("as" => "dashboard.approve", "before"=>"campaign.exists",   "uses" => "DashboardController@postApproveMedia"));
    Route::post('/media/unapprove', array("as" => "dashboard.unapprove", "before"=>"campaign.exists",   "uses" => "DashboardController@postUnApproveMedia"));
    Route::post('/media/trash', array("as" => "dashboard.trash", "before"=>"campaign.exists",   "uses" => "DashboardController@postTrashMedia"));
    Route::post('/media/untrash', array("as" => "dashboard.untrash", "before"=>"campaign.exists",   "uses" => "DashboardController@postUnTrashMedia"));
    Route::post('/media/edit', array("as" => "dashboard.edit", "before"=>"campaign.exists",   "uses" => "DashboardController@postEditMedia"));

    Route::post('/notifications/viewAll', array("as" => "dashboard.notifications.viewAll", "before"=>"campaign.exists",   "uses" => "DashboardController@postViewAllNotifications"));


    Route::group(array('prefix' => 'campaign'), function()
    {
        Route::get('/', array("as" => "campaign.index", "uses" => "CampaignController@getIndex"));
        Route::get('/edit/{campaign}', array("as" => "campaign.edit", "uses" => "CampaignController@getCreate"));

        Route::get('/new', array("as" => "campaign.new", "uses" => "CampaignController@getCreate"));
        Route::get('/search/hashtag', array("as" => "campaign.search.hashtag", "uses" => "CampaignController@getHashtagSearch"));
        Route::post('/edit', array("as" => "campaign.post.edit", "uses" => "CampaignController@postEdit"));
        Route::post('/delete', array("as" => "campaign.post.delete", "uses" => "CampaignController@postDelete"));

        Route::get('/autolink/{campaign}', array("as" => "campaign.autolink", "uses" => "CampaignController@getAutolink"));
        Route::post('/autolink/{campaign}', array("as" => "campaign.post.autolink", "uses" => "CampaignController@postAutolink"));
    });

    Route::group(array('prefix' => 'product'), function()
    {
        Route::get('/',         array("as" => "products.index",   "uses" => "ProductController@getList"));


        Route::post('/new',         array("as" => "product.post.new",   "uses" => "ProductController@postNew"));
        Route::post('/edit',         array("as" => "product.post.edit",   "uses" => "ProductController@postNew"));
        Route::post('/trash',         array("as" => "product.post.trash",   "uses" => "ProductController@postTrash"));
        Route::post('/csv',         array("as" => "product.post.csv",   "uses" => "ProductController@postCsv"));

        Route::get('/search',         array("as" => "product.get.search",   "uses" => "ProductController@getSearch"));
        Route::post('/attach/media',         array("as" => "product.attach.media",   "uses" => "ProductController@postAttachToMedia"));
        Route::post('/unattach/media',         array("as" => "product.unattach.media",   "uses" => "ProductController@postUnAttachToMedia"));

    });

    Route::group(array('prefix' => 'analytics', "before"=>"campaign.exists"), function()
    {
        Route::get('/', array("as" => 'analytics.index', 'uses' => 'AnalyticController@getIndex'));
    });


    Route::group(array('prefix' => 'widgets', "before"=>"campaign.exists"), function()
    {
        Route::get('/', array("as" => 'widgets.index', 'uses' => 'WidgetController@getIndex'));
        Route::get('/new', array("as" => 'widgets.get.new', 'uses' => 'WidgetController@getNew'));
        Route::get('/edit/{widget_id}', array("as" => 'widgets.get.edit', 'uses' => 'WidgetController@getNew'));
        Route::get('/duplicate/{widget_id}', array("as" => 'widgets.get.duplicate', 'uses' => 'WidgetController@getDuplicate'));
        Route::post('/new', array("as" => 'widgets.post.new', 'uses' => 'WidgetController@postNew'));
        Route::get('/preview/{public_uid}', array("as" => 'widgets.preview', 'uses' => 'WidgetController@getPreview'));
        Route::get('/css/edit/{public_uid}', array("as" => 'widgets.css-editor', 'uses' => 'WidgetController@getCSSEditor'));
        Route::get('/html/edit/{public_uid}', array("as" => 'widgets.html-editor', 'uses' => 'WidgetController@getHTMLEditor'));

        /** AJAX STUFF */

        Route::post("/active/toggle", array("as" => "widgets.post.active.toggle", "uses" => "WidgetController@postActiveToggle"));
        Route::post("/delete", array("as" => "widgets.post.delete", "uses" => "WidgetController@postDelete"));
        Route::post("/css/save", array("as" => "widgets.post.css.save", "uses" => "WidgetController@postCSSSave"));
        Route::post("/html/save", array("as" => "widgets.post.html.save", "uses" => "WidgetController@postHTMLSave"));
    });


    /**
     *
     * Admin Routes
     *
     */

    Route::group(array('prefix' => 'admin', 'before' => 'auth.dashboard.admin'), function()
    {
        Route::group(array('prefix' => 'user'), function()
        {
            Route::get('/', array("as" => 'admin.user.index', 'uses' => 'AdminUserController@getIndex'));
            Route::get('/disabled', array("as" => 'admin.user.disabled.index', 'uses' => 'AdminUserController@getDisabledIndex'));

            Route::get('/disable/{user_id}', array("as" => 'admin.user.disable', 'uses' => 'AdminUserController@getDisable'));
            Route::get('/enable/{user_id}', array("as" => 'admin.user.enable', 'uses' => 'AdminUserController@getEnable'));

            Route::get('/new', array("as" => 'admin.user.create', 'uses' => 'AdminUserController@getCreate'));
            Route::post('/new', array("as" => 'admin.user.create.post', 'uses' => 'AdminUserController@postCreate'));

            Route::get('/edit/{user_id}', array("as" => 'admin.user.edit', 'uses' => 'AdminUserController@getCreate'));
            Route::post('/edit/{user_id}', array("as" => 'admin.user.edit.post', 'uses' => 'AdminUserController@postCreate'));

            Route::get('/become/{user_id}', array("as" => 'admin.user.become', 'uses' => 'AdminUserController@getBecome'));
        });

        Route::group(array('prefix' => 'report'), function()
        {
            Route::get('/', array("as" => 'admin.report.index', 'uses' => 'ReportController@getIndex'));
            Route::post('/approve', array("as" => 'admin.report.approve.post', 'uses' => 'ReportController@postApprove'));
            Route::post('/decline', array("as" => 'admin.report.decline.post', 'uses' => 'ReportController@postDecline'));
        });
    });
});


Route::get('/preview/iframe/{public_uid}', array("as" => 'widgets.public.iframe', 'uses' => 'WidgetController@getPreview'));


Route::post("/report", array('as' => 'report.post', "after"=>"cookie.remove",     'uses' => 'ReportController@postReport'));

Route::get("/terms-and-privacy", array('as' => 'pages.tandc', "after"=>"cookie.remove",     'uses' => 'HomeController@getTermsAndPrivacy'));
Route::get("/sample", array('as' => 'pages.sample', "after"=>"cookie.remove",     'uses' => 'HomeController@getSample'));
Route::get("/company", array('as' => 'pages.company', "after"=>"cookie.remove",     'uses' => 'HomeController@getCompany'));
Route::get("/contact", array('as' => 'pages.contact',  "after"=>"cookie.remove",    'uses' => 'HomeController@getContact'));
Route::post("/contact", array('as' => 'pages.contact.post',  "after"=>"cookie.remove",    'uses' => 'HomeController@postContact'));
Route::get("/", array('as' => 'pages.index',  "after"=>"cookie.remove",    'uses' => 'HomeController@getIndex'));