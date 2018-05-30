<?php

namespace Rubix\Engine\Classifiers;

use Rubix\Engine\Supervised;
use Rubix\Engine\Persistable;
use Rubix\Engine\Datasets\Dataset;
use Rubix\Engine\Datasets\Labeled;
use Rubix\Engine\NeuralNet\Network;
use Rubix\Engine\NeuralNet\Layers\Input;
use Rubix\Engine\NeuralNet\Layers\Binary;
use Rubix\Engine\NeuralNet\Optimizers\Adam;
use Rubix\Engine\NeuralNet\Optimizers\Optimizer;
use InvalidArgumentException;
use RuntimeException;

class LogisticRegression implements Supervised, BinaryClassifier, Persistable
{
    /**
     * The maximum number of training epochs. i.e. the number of times to iterate
     * over the entire training set.
     *
     * @var int
     */
    protected $epochs;

    /**
     * The number of training samples to consider per iteration of gradient descent.
     *
     * @var int
     */
    protected $batchSize;

    /**
     * The gradient descent optimizer.
     *
     * @var \Rubix\Engine\NeuralNet\Optimizers\Optimizer
     */
    protected $optimizer;

    /**
     * The L2 regularization parameter.
     *
     * @var float
     */
    protected $alpha;

    /**
     * The underlying computational graph.
     *
     * @param \Rubix\Engine\NeuralNet\Network
     */
    protected $network;

    /**
     * @param  int  $epochs
     * @param  int  $batchSize
     * @param  \Rubix\Engine\NeuralNet\Optimizers\Optimizer  $optimizer
     * @param  float  $alpha
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(int $epochs = 10, int $batchSize = 5, Optimizer $optimizer = null,
                                float $alpha = 1e-4)
    {
        if ($epochs < 1) {
            throw new InvalidArgumentException('Estimator must train for at'
                . ' least 1 epoch.');
        }

        if ($batchSize < 1) {
            throw new InvalidArgumentException('Batch cannot have less than'
                . ' 1 sample.');
        }

        if (!isset($optimizer)) {
            $optimizer = new Adam();
        }

        $this->batchSize = $batchSize;
        $this->epochs = $epochs;
        $this->optimizer = $optimizer;
        $this->alpha = $alpha;
    }

    /**
     * Perform mini-batch gradient descent with given optimizer over the training
     * set and update the input weights accordingly.
     *
     * @param  \Rubix\Engine\Datasets\Labeled  $dataset
     * @return void
     */
    public function train(Labeled $dataset) : void
    {
        $this->network = new Network(new Input($dataset->numColumns()),
            [], new Binary($dataset->possibleOutcomes(), $this->alpha));

        foreach ($this->network->initialize()->parametric() as $layer) {
            $this->optimizer->initialize($layer);
        }

        for ($epoch = 1; $epoch <= $this->epochs; $epoch++) {
            foreach ($dataset->randomize()->batch($this->batchSize) as $batch) {
                $this->network->feed($batch->samples())
                    ->backpropagate($batch->labels());

                foreach ($this->network->parametric() as $layer) {
                    $layer->update($this->optimizer->step($layer));
                }
            }
        }
    }

    /**
     * Read the activation of the neuron and make a prediction.
     *
     * @param  \Rubix\Engine\Datasets\Dataset  $samples
     * @return array
     */
    public function predict(Dataset $samples) : array
    {
        $this->network->feed($samples->samples());

        $predictions = [];

        foreach ($this->network->output()->activations() as $activations) {
            foreach ($activations as $class => $activation) {
                $predictions[] = $class;
            }
        }

        return $predictions;
    }
}
