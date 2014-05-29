<?php

class WidgetDistributionController extends BaseController {

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

    public function getUpload()
    {
        return View::make("");
    }

    public function getUniqueWidgetRedirect($public_uid)
    {
        $widget = Widget::where("public_uid", $public_uid)->first();
        if (!$widget)
        {
            App::abort(404);
        }
        return Redirect::route("widgetdist.get.unique", array("campaign_id" => $widget->site_id, "public_uid" => $widget->public_uid), 301);
    }
    public function getFeedRedirect($public_uid, $extension)
    {
        $widget = Widget::where("public_uid", $public_uid)->first();
        if (!$widget)
        {
            App::abort(404);
        }
        return Redirect::route("widgetdist.get.feed", array("campaign_id" => $widget->site_id, "public_uid" => $widget->public_uid, "extension" => $extension), 301);
    }

    public function getUniqueWidget($campaign_id, $public_uid)
    {
        //$url = Input::get("url");
        $widget = Widget::with("site", "site.hashtags")->where("public_uid", $public_uid)->first();
        if ($widget->type != "embed")
            return $this->widgetAbort(false);

        $view = "widget_dist.type." . $widget->style;
        if (!View::exists($view))
            return $this->widgetAbort("Error: Invalid Widget View Configuration");

        $site = $widget->site;

        $wm = App::make("widgetMedia");

        $requestUrl = Input::get("u");
        $media = $wm->mediaFromWidget($widget, $requestUrl, $widget->per_page);
        if (!$media)
            return $this->widgetAbort();

        $content = View::make($view, array("media" => $media, "site" => $site, "widget" => $widget));

        return Response::make($content, 200, array("content-type" => "application/javascript"));
    }

    private function widgetAbort($error = false)
    {
        if ($error)
            return Response::make("window.console && console.log('".$error."');", 200, array("content-type" => "application/javascript"));
        else
            return Response::make("", 200, array("content-type" => "application/javascript"));
    }


    public function getFeed($campaign_id, $public_uid, $extension)
    {
        Config::set('profiler::profiler', FALSE);

        $per_page = (int)Input::get("per_page", 20);

        if ($per_page > 50 || $per_page < 1)
        {
            $per_page = 20;
        }

        $widget = Widget::with("site", "site.hashtags")->where("public_uid", $public_uid)->first();

        if (!$widget || $widget->type != "xml" || !$widget->active)
        {
            //return Response::make($xml->asXML(), 200, array("content-type" => "text/xml"));
        }

        $hashtags = array();
        foreach($widget->site->hashtags as $hashtag)
        {
            $hashtags[$hashtag->id] = $hashtag->hashtag;
        }

        $prov = Provider::all();
        $providers = array();
        foreach($prov as $p)
        {
            $providers[$p->id] = $p->name;
        }

        $requestUrl = Input::get("u");

        $wm = App::make("widgetMedia");
        $medias = $wm->mediaFromWidget($widget, $requestUrl, $widget->per_page);
        if (!$medias)
            return $this->widgetAbort();

        $medias->addQuery("per_page", $per_page);

        $totalPages = ceil($medias->getTotal()/$medias->getPerPage());
        $nextPage = ($medias->getCurrentPage()+1 > $totalPages)?$medias->getUrl($medias->getCurrentPage()):$medias->getUrl($medias->getCurrentPage()+1);
        $prevPage = ($medias->getCurrentPage()-1 < 1)?$medias->getUrl($medias->getCurrentPage()):$medias->getUrl($medias->getCurrentPage()-1);

        $meta = array(
            "pagination" =>
            array(
                "next_page" => $nextPage,
                "prev_page" => $prevPage,
                "current_page" => (int)$medias->getCurrentPage(),
                "total_pages" => (int)$totalPages,
                "total_posts" => (int)$medias->getTotal(),
                "per_page" => (int)$per_page,
            )
        );

        $medias = $medias->getCollection()->toArray();

        foreach($medias as &$media)
        {
            $media['provider'] = $providers[$media['provider_id']];
            $media['links'] = $media['products'];
            //var_dump($media['links']);
            foreach($media['links'] as &$link)
            {
                if (isset($link['pivot']))
                    unset($link['pivot']);
            }
            unset($media['products'], $media['provider_id']);
        }

        //die;

        $data = array(
            "feed" => $medias,
            "meta" => $meta,
        );

        switch($extension)
        {
            case "xml":
                $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><dittto></dittto>");
                $this->arrayToXML($data, $xml);
                return Response::make($xml->asXML(), 200, array("content-type" => "text/xml"));
                break;

            case "json":
                return Response::json($data);
                break;

            default:
                App::abort(404);
                break;
        }


    }

    // function defination to convert array to xml
    private function  arrayToXML($array, &$xml, $depth = 0) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $xml->addChild("$key");
                    $this->arrayToXML($value, $subnode, ++$depth);
                }
                else{
                    $keyName = "post";
                    if ($depth > 1)
                        $keyName = "link";
                    $subnode = $xml->addChild($keyName);
                    $this->arrayToXML($value, $subnode, ++$depth);
                }
            }
            else {
                    $xml->addChild("$key", htmlspecialchars($value));
            }
        }
    }

   /* private function mediaFromWidget(Widget $widget, $paginate=false)
    {
        if (!$widget)
            return $this->widgetAbort();

        if (!$widget->active)
            return $this->widgetAbort();



        $site = $widget->site;

        $hashtags = array();
        foreach($site->hashtags as $hashtag)
        {
            $hashtags[] = $hashtag->id;
        }

        $requestUrl = Input::get("u");
        $product = null;
        if ($requestUrl)
        {
            $url = Product::normalizeURL($requestUrl);
            $product = Product::where("url", $url)->first();
        }

        $media = null;

        switch ($widget->behaviour)
        {
            case Widget::BEHAVIOUR_STREAMS:
            case Widget::BEHAVIOUR_HASHTAG:
            case Widget::BEHAVIOUR_DEFAULT:
            default:
                $media = Media::with("products")->Approved()->whereNotNull("social_point.image_url")->whereIn("hashtag_id", $hashtags)->orderBy("approved_at", "DESC");

                if ($product)
                {
                    $media->join("media_product", "social_point.id", "=", "media_id")
                        ->where("media_product.product_id", $product->id);
                }
                break;
        }

        if (!$media)
            return $this->widgetAbort("Error: Invalid Widget Configuration");

        if ($paginate && is_numeric($paginate))
            return $media->paginate($paginate);

        return $media->get();
    }*/

    /**
     * This is DEPRECATED, use unique widget instead.
     *
     * @deprecated
     * @param $public_uid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getWidget($public_uid)
	{
        Config::set('profiler::profiler', FALSE);

        //$url = Input::get("url");
        $site = Site::with("hashtags")->where("public_uid", $public_uid)->first();

        if (!$site)
        {
            App::abort(404);
        }

        $hashtags = array();
        foreach($site->hashtags as $hashtag)
        {
            $hashtags[] = $hashtag->id;
        }

        $requestUrl = Input::get("u");
        $product = null;
        if ($requestUrl)
        {
            $url = Product::normalizeURL($requestUrl);
            $product = Product::where("url", $url)->first();
        }

        $media = Media::with("products")->Approved()->whereNotNull("image_url")->whereIn("hashtag_id", $hashtags)->take(20)->orderBy("approved_at", "DESC");

        $media = $media->get();

        $content = View::make("widget_dist.legacy.carousel1_legacy", array("media" => $media, "site" => $site,"caption" => Input::get("caption", $site->caption), "title" => Input::get("title", $site->name), "footer" => Input::get("footer"), "gallery" => Input::get("gallery")));
        return Response::make($content, 200, array("content-type" => "application/javascript"));

	}

}