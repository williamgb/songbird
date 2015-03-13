<?php
namespace Songbird\Document;

use Illuminate\Support\Collection;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Songbird\ContainerResolverTrait;
use Songbird\Document\Document;

class Repository implements ContainerAwareInterface
{
    use ContainerAwareTrait, ContainerResolverTrait;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $files;

    /**
     * @param \Illuminate\Support\Collection $files
     */
    public function __construct(Collection $files)
    {
        $this->files = $files;
    }

    public function findAll()
    {
        return $this->map(function ($file) {
            return new Document($file);
        });
    }

    public function find($id)
    {
        $file = $this->files->where('id', $id);

        if ($file->isEmpty()) {
            $file = $this->files->where('id', '404');
        }

        return $file->first();
    }

    public function __call($name, $args = null)
    {
        $this->files = call_user_func_array([$this->files, $name], $args);

        return $this->files;
    }
}