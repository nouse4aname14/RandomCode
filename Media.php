<?php

class Media extends Eloquent
{
    protected $table = "social_point";
    protected $softDelete = true;
    protected $fillable = array('username', 'fullname', 'profile_image_url', 'hashtag_id', 'provider_point_uid', 'body', 'provider_id', 'provider_created_at', 'social_url');
    protected $hidden = array('hashtag_id','provider_point_uid', 'approved_at', 'approved_by', 'deleted_at', 'provider_created_at', 'updated_at', 'created_at');
    //'social_image_url', 'social_video_url',


    public function scopeApproved($query)
    {
        return $query->whereNotNull("approved_at");
    }

    public function scopeUnApproved($query)
    {
        return $query->whereNull("approved_at");
    }

    public function getFullnameAttribute($text)
    {
        return $this->decodeText($text);
    }

    public function getBodyAttribute($body)
    {
       return $this->decodeText($body);
    }

    private function decodeText($text)
    {
        //var_dump(mb_detect_encoding($body));
        $body = preg_replace("/\\\\U([0-9A-f]{8})/", "&#x\\1;", $text);
        $body = str_replace("000", "", $body);
        $body = preg_replace("/\\\\u([0-9A-f]{4})/", "&#x\\1;", $body);
        $body = str_replace("\\n", "<br/>", $body);
        return html_entity_decode($body, ENT_NOQUOTES, 'UTF-8');
    }

    public function getProviderCreatedAtAttribute($datetime)
    {
        return \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $datetime, "UTC");
    }

    public function hashtag()
    {
        $this->belongsTo("Hashtag");
    }

    public function provider()
    {
        $this->belongsTo("Provider");
    }

    public function approvedBy()
    {
        $this->belongsTo("User");
    }

    public function medias()
    {
        return $this->hasMany("Media");
    }
    public function reports()
    {
        return $this->hasMany("MediaReport");
    }

    public function products()
    {
        return $this->belongsToMany("Product");
    }

    //returns a Media object found in the database
    private function findById($media_id)
    {
        $media = DB::table('social_point')->where('id', "$media_id");
        return $media;
    }

    //returns true if Media object is type Video
    public function isVideo($media_id = 0)
    {
        if($media_id == 0)
        {
            if($this->media_type == '1') return true;
            else return false;
        }
        else
        {
            $media = findById($media_id);
            if($media->media_type == '1') return true;
            else return false;
        }
        
    }

    //returns true if Media object has cloud urls
    public function hasCdnUrls($media_id = 0)
    {
        if($media_id == 0)
        {
            if($this->isVideo())
            {
                if($this->image_url && $this->video_url) return true;
                else return false;
            }
            else
            {
                if($this->image_url) return true;
                else return false;
            }
        }
        else
        {
            $media = findById($media_id);

            if($media->isVideo())
            {
                if($media->image_url && $media->video_url) return true;
                else return false;
            }
            else
            {
                if($media->image_url) return true;
                else return false;
            }
        }
    }

    //returns the CDN image url, if available (non-CDN version otherwise), for the requested media, defaults to $this media
    //if $thumb = true, it returns the thumbnail version of the image if it's available, otherwise it returns the fullsize image
    public function getImageUrl($media_id = 0, $thumb = false)
    {
        if($media_id == 0)
        {
            if($this->hasCdnUrls())
            {
                if($this->image_thumb_url && $thumb) return $this->image_thumb_url;
                else return $this->image_url;
            }
            else return $this->social_image_url;
        }
        else
        {
            $media = findById($media_id);

            if($media->hasCdnUrls())
            {
                if($media->image_thumb_url && $thumb) return $media->image_thumb_url;
                else return $media->image_url;
            }
            else return $media->social_image_url;
        }
    }

    //returns the CDN video url, if available (non-CDN version otherwise), for the requested media, defaults to $this media
    public function getVideoUrl($media_id = 0)
    {
        if($media_id == 0)
        {
            if($this->hasCdnUrls())
            {
                return $this->video_url;
            }
            else return $this->social_video_url;
        }
        else
        {
            $media = findById($media_id);

            if($media->hasCdnUrls())
            {
                return $media->video_url;
            }
            else return $media->social_video_url;
        }
    }

    public static function clearCache($site_id)
    {
        if ($site_id && $site_id > 0)
        {
            $bans = array(
                "/v1/feed/" . $site_id . "/",
                "/gallery/" . $site_id . "/",
                "/widget/" . $site_id . "/",
            );
            Acetone::banMany($bans);
        }
    }

    /**
     * Listen for save event
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function($model)
        {
            if ($model->approved_at)
            {

                $hashtag = Hashtag::findOrFail($model->hashtag_id);

                if ($hashtag)
                {
                    $site_id = $hashtag->site_id;
                    Media::clearCache($site_id);
                }
            }
        });
    }

}