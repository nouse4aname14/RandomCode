<?php

class CampaignController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/

	public function getIndex()
	{
        $campaigns = Site::with("hashtags")->where('user_id', Sentry::getUser()->id)->get();

		return View::make('campaign.index', array("campaigns" => $campaigns));
	}

    public function getCreate($campaign_id = null)
    {
        $campaign = null;
        if ($campaign_id)
        {
            $campaign = Site::with("hashtags")->where("user_id", Sentry::getUser()->id)->find((int)$campaign_id);
            if (!$campaign)
                App::abort(404);
        }
        else
            $campaign = new Site;

        return View::make("campaign.create", array("campaign" => $campaign));
    }


    public function postEdit()
    {
        $campaign = null;
        $campaign_id = Input::get("campaign_id");
        $hashtags = Input::get("hashtag", array());



        if ($campaign_id)
        {
            $campaign = Site::with("hashtags")->where("user_id", Sentry::getUser()->id)->find((int)$campaign_id);
            if (!$campaign)
                App::abort(404);
        }
        else
            $campaign = new Site;

        $errors = array();
        if (!is_array($hashtags) || count($hashtags) <= 0)
        {
            $errors[] = "You must include at least one hashtag";
        }

        if (!Input::get("name") || strlen(Input::get("name")) <= 1)
        {
            $errors[] = "You must include a name for your campaign";
        }

        if (count($errors)>0)
        {
            if ($campaign->id)
            {
                return Redirect::route("campaign.edit", array("campaign"=>$campaign->id))->with("warning", $errors);
            }else{
                return Redirect::route("campaign.new")->with("warning", $errors);
            }

        }

        $campaign->name = Input::get("name");
        $campaign->url = Input::get("url", " ");
        $campaign->gallery_url = Input::get("gallery_url", " ");

        $success = "Successfully created the Campaign '%s'";
        if (!$campaign->id)
        {
            //post on demand for all the hashtags!

            Sentry::getUser()->sites()->save($campaign);
        }else{
            $success = "Successfully updated '%s'";
            $campaign->save();
        }



        $hashtagOrig = array();
        foreach($campaign->hashtags as $hashtag)
        {
            if (!in_array($hashtag->hashtag, $hashtags))
            {
                $hashtag->delete();
            }else{
                $hashtagOrig[] = $hashtag->hashtag;
            }

        }
        foreach($hashtags as $hashtag)
        {
            if (!in_array($hashtag, $hashtagOrig))
            {
                //check its not a reactivation!
                $check = Hashtag::withTrashed()->where("site_id", $campaign->id)->where("hashtag", str_replace("#", "", $hashtag))->first();
                if ($check)
                {
                    $check->restore();
                }else{
                    $newHashtag = new Hashtag(array("hashtag" => trim($hashtag), "active" => true));
                    $campaign->hashtags()->save($newHashtag);
                    Queue::connection()->push("ondemand_hashtags", array("id" => $newHashtag->id, "hashtag" => $newHashtag->getRawHashtag(), "scan_frequency" =>10, "scan_depth" => 50), "ondemand_hashtags");
                }
            }
        }

        return Redirect::route("campaign.index")->with("success", sprintf($success, $campaign->name));
    }

    public function getHashtagSearch()
    {

        $q = trim(str_replace("#", "", Input::get("q")));

        $instagram = new \Instagram\Instagram();
        $instagram->setClientID(Config::get("social.instagram.client_id"));
        $results = $instagram->searchTags($q);

        $response = array();
        foreach($results as $result)
        {
            $response[] = array("name" => "#".$result->name,"id" => "#".$result->name, "count" => $result->media_count);
        }

        return Response::json($response);

    }

    public function postDelete()
    {
        $id = (int)Input::get("id");
        $user = Sentry::getUser();
        if ($id == Session::get("active_site"))
        {
            $newSite = Site::where("user_id", $user->id)->first();
            if (!$newSite)
            {
                return Response::json(array("code" => 404));
            }
            Session::set("active_site", $newSite->id);
        }

        $campaign = Site::where("user_id", $user->id)->find((int)$id);
        if (!$campaign)
        {
            return Response::json(array("code" => 404));
        }
        if ($campaign->delete())
        {
            return Response::json(array("code" => 200));
        }
        return Response::json(array("code" => 404));
    }

    public function getAutolink($campaign_id)
    {
        $campaign = Site::with("hashtags", "hashtags.products")->where("user_id", Sentry::getUser()->id)->where("id", $campaign_id)->first();
        if (!$campaign)
            App::abort(404);

        return View::make("campaign.autolink", array("campaign" => $campaign));
    }

    public function postAutolink($campaign_id)
    {
        $campaign = Site::with("hashtags", "hashtags.products")->where("user_id", Sentry::getUser()->id)->where("id", $campaign_id)->first();
        if (!$campaign)
            App::abort(404);

        foreach($campaign->hashtags as $hashtag)
        {
            $links = array_keys(Input::get("product-" . $hashtag->id, array()));
            $links = array_map('intval', str_replace(array("-a", "-e", "-d"), "", $links));
            $hashtag->products()->sync($links);
        }

        return Redirect::route("campaign.index")->with("success", "Updated the advanced campaign rules.");
    }

}