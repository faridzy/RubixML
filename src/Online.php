<?php

namespace Rubix\ML;

use Rubix\ML\Datasets\Dataset;

interface Online extends Learner
{
    /**
     * Perform a partial train on the learner.
     *
     * @param  \Rubix\ML\Datasets\Dataset  $dataset
     * @return void
     */
    public function partial(Dataset $dataset) : void;
}
