<?php
namespace App\Models\v1;

use App\Libraries\v1\Helpers;

class Location extends \Eloquent
{
    protected $table = "locations";

    protected $fillable = array("lat", "lng", "name", "address", "city", "state");

    protected $hidden = array("id");

    public function __construct($attributes = array(), $exists = false)
    {
        parent::__construct($attributes, $exists);
        $this->public_uid = \Str::random(8);
    }

    public static function findWithPublicID($id)
    {
        return self::where("public_uid", "=", $id)->first();
    }

    public static function findWithCoords($lat, $lng)
    {
        return self::where("lat", "=", $lat)->where("lng", "=", $lng)->first();
    }

    public static function cleanLocationData($locationData){
        if (!isset($locationData['formatted_address']))
        {
            return array();
        }
        $address = explode(',', $locationData['formatted_address']);
        $zipcode = $locationData['zipcode'];
        if(!preg_match('/^\d{5}(-\d{4})?$/', $zipcode)){
            $zipcode = NULL;
        }
        if(isset($address[2]))
        {
            if (strlen($address[2]) > 2){
            $state = Helpers::format_state($address[2], 'abbr');
            } else {
                $state = strtoupper($address[2]);
            }
        }
        return array("lat" => $locationData['lat'], "lng" => $locationData['lng'], "name" => $locationData['name'], "address" => $address[0], "city" => $address[1], "state" => $state, "zipcode" => $zipcode);
    }

    public function runs()
    {
        return $this->hasMany("App\\Models\\v1\\Run", "location_id");
    }
}