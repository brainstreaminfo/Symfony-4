<?php

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class Notifiable
 * @package App\Annotation
 *
 * @Annotation
 * @Annotation\Target("CLASS")
 */
class Notifiable
{
    /**
     * @Required()
     * @var string
     */
    public $name;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return Notifiable
     */
    public function setName(?string $name)
    {
        $this->name = $name;

        return $this;
    }
}
