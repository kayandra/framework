<?php

namespace Illuminate\Bus;

use Closure;
use Illuminate\Collections\Collection;
use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\SerializableClosure;
use Throwable;

class PendingBatch
{
    /**
     * The jobs that belong to the batch.
     *
     * @var \Illuminate\Collections\Collection
     */
    public $jobs;

    /**
     * The batch options.
     *
     * @var array
     */
    public $options = [];

    /**
     * Create a new pending batch instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Illuminate\Collections\Collection  $jobs
     * @return void
     */
    public function __construct(Container $container, Collection $jobs)
    {
        $this->container = $container;
        $this->jobs = $jobs;
    }

    /**
     * Add a callback to be executed after the batch has finished executing.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function then(Closure $callback)
    {
        $this->options['then'][] = new SerializableClosure($callback);

        return $this;
    }

    /**
     * Add a callback to be executed after the first failing job in the batch.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function catch(Closure $callback)
    {
        $this->options['catch'][] = new SerializableClosure($callback);

        return $this;
    }

    /**
     * Dispatch the batch.
     *
     * @return void
     */
    public function dispatch()
    {
        $repository = $this->container->make(BatchRepository::class);

        try {
            $batch = $repository->store($this);

            $batch->add($this->jobs);
        } catch (Throwable $e) {
            if (isset($batch)) {
                $repository->delete($batch->id);
            }

            throw $e;
        }

        return $batch;
    }
}
