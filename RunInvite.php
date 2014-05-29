<?php
namespace App\Models\v1;

class RunInvite extends \Eloquent
{
    protected $table = "run_invites";

    protected $fillable = array("run_id", "user_id");

    protected $hidden = array("id", "run_id", "user_id");

    public $includes = array("run", "user", "runOrder");

    protected $softDelete = true;


    public function __construct($attributes = array(), $exists = false)
    {
        parent::__construct($attributes, $exists);
        $this->public_uid = \Str::random(8);
    }

    public static function findWithPublicID($id)
    {
        return self::with("run")->where("public_uid", "=", $id)->first();
    }

    public function getPublicLink()
    {
        return \Config::get("app.invite_base_url") . "/" . $this->public_uid;
    }

    public function toArray()
    {
        $this->setAttribute("public_link", $this->getPublicLink());
        $array = parent::toArray();
        return $array;
    }

    public function runOrder()
    {
        return $this->hasOne("App\\Models\\v1\\RunOrder");
    }

    public function run()
    {
        return $this->belongsTo("App\\Models\\v1\\Run");
    }

    public function user()
    {
        return $this->belongsTo("App\\Models\\v1\\User");
    }

    public function delete()
    {
        //soft delete order attached to the invite
        $this->runOrder()->delete();

        return parent::delete();
    }
}