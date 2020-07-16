<?php


namespace PDMFC\Saml2Idp;


use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    /**
     * @param $entityId
     * @return self
     */
    public static function findByEntityId($entityId)
    {
        return static::where('entity_id',$entityId)->first();
    }

    /**
     *
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(static function(Application $sp) {
            $sp->entity_id = base64_encode($sp->destination_endpoint);
        });
    }

}
