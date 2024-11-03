<?php

namespace Firevel\FirestoreMirror;

class ModelObserver
{
    /**
     * The class names that mirroring is disabled for.
     *
     * @var array
     */
    protected static $mirroringDisabledFor = [];

    /**
     * Enable mirroring for the given class.
     *
     * @param  string  $class
     * @return void
     */
    public static function enableMirroringFor($class)
    {
        unset(static::$mirroringDisabledFor[$class]);
    }

    /**
     * Disable mirroring for the given class.
     *
     * @param  string  $class
     * @return void
     */
    public static function disableMirroringFor($class)
    {
        static::$mirroringDisabledFor[$class] = true;
    }

    /**
     * Determine if mirroring is disabled for the given class or model.
     *
     * @param  object|string  $class
     * @return bool
     */
    public static function mirroringDisabledFor($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return isset(static::$mirroringDisabledFor[$class]);
    }

    /**
     * Handle the saved event for the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function saved($model)
    {
        if (static::mirroringDisabledFor($model)) {
            return;
        }

        $model->mirrorToFirestore();
    }

    /**
     * Handle the deleted event for the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function deleting($model)
    {
        if (static::mirroringDisabledFor($model)) {
            return;
        }

        $model->deleteFromFirestore();
    }
}
