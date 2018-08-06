<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\console\output;

use Exception;
use nb\console\input\Input;
use nb\console\output\Ask;
use nb\console\output\Descriptor;
use nb\console\output\Question;
use nb\console\output\question\Choice;
use nb\console\output\question\Confirmation;

/**
 * Class Output
 * @package nb\console\output
 *
 * @see     \nb\console\output\Terminal::setDecorated
 * @method void setDecorated($decorated)
 *
 * @method void info($message)
 * @method void error($message)
 * @method void comment($message)
 * @method void warning($message)
 * @method void highlight($message)
 * @method void question($message)
 */
class Output {

    const VERBOSITY_QUIET = 0;
    const VERBOSITY_NORMAL = 1;
    const VERBOSITY_VERBOSE = 2;
    const VERBOSITY_VERY_VERBOSE = 3;
    const VERBOSITY_DEBUG = 4;

    const OUTPUT_NORMAL = 0;
    const OUTPUT_RAW = 1;
    const OUTPUT_PLAIN = 2;

    private $verbosity = self::VERBOSITY_NORMAL;

    /** @var Buffer|Console|Nothing */
    private $handle = null;

    protected $styles = [
        'info',
        'error',
        'comment',
        'question',
        'highlight',
        'warning'
    ];

    public function __construct($driver = 'console') {
        //$class = '\\nb\\console\\output\\driver\\' . ucwords($driver);
        //$this->handle = new $class($this);

        $this->handle = new Terminal($this);
    }

    public function ask(Input $input, Question $question) {
        $ask = new Ask($input, $this, $question);
        return $ask->run();
        /*
        $answer = $ask->run();

        if ($input->isInteractive()) {
            $this->newLine();
        }
        return $answer;
        */
    }
    /*
    public function confirm(Input $input, $question, $default = true) {
        return $this->askQuestion($input, new Confirmation($question, $default));
    }

    public function ask_bak(Input $input, $question, $default = null, $validator = null) {
        $question = new Question($question, $default);
        $question->setValidator($validator);

        return $this->askQuestion($input, $question);
    }

    public function askHidden(Input $input, $question, $validator = null) {
        $question = new Question($question);

        $question->setHidden(true);
        $question->setValidator($validator);

        return $this->askQuestion($input, $question);
    }


    public function choice(Input $input, $question, array $choices, $default = null) {
        if (null !== $default) {
            $values = array_flip($choices);
            $default = $values[$default];
        }

        return $this->askQuestion($input, new Choice($question, $choices, $default));
    }

    protected function askQuestion(Input $input, Question $question) {
        $ask = new Ask($input, $this, $question);
        $answer = $ask->run();

        if ($input->isInteractive()) {
            $this->newLine();
        }

        return $answer;
    }
    */

    protected function block($style, $message) {
        $this->writeln("<{$style}>{$message}</$style>");
    }

    /**
     * 输出空行
     * @param int $count
     */
    public function newLine($count = 1) {
        $this->write(str_repeat(PHP_EOL, $count));
    }

    /**
     * 输出信息并换行
     * @param string $messages
     * @param int $type
     */
    public function writeln($messages, $type = self::OUTPUT_NORMAL) {
        $this->write($messages, true, $type);
    }

    /**
     * 输出信息
     * @param string $messages
     * @param bool $newline
     * @param int $type
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL) {
        $this->handle->write($messages, $newline, $type);
    }

    public function renderException(\Exception $e) {
        $this->handle->renderException($e);
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity($level) {
        $this->verbosity = (int)$level;
    }

    /**
     * {@inheritdoc}
     */
    public function getVerbosity() {
        return $this->verbosity;
    }

    public function isQuiet() {
        return self::VERBOSITY_QUIET === $this->verbosity;
    }

    public function isVerbose() {
        return self::VERBOSITY_VERBOSE <= $this->verbosity;
    }

    public function isVeryVerbose() {
        return self::VERBOSITY_VERY_VERBOSE <= $this->verbosity;
    }

    public function isDebug() {
        return self::VERBOSITY_DEBUG <= $this->verbosity;
    }

    public function describe($object, array $options = []) {
        $descriptor = new Descriptor();
        $options = array_merge([
            'raw_text' => false,
        ], $options);

        $descriptor->describe($this, $object, $options);
    }

    public function __call($method, $args) {
        if (in_array($method, $this->styles)) {
            array_unshift($args, $method);
            return call_user_func_array([$this, 'block'], $args);
        }

        if ($this->handle && method_exists($this->handle, $method)) {
            return call_user_func_array([$this->handle, $method], $args);
        }
        else {
            throw new Exception('method not exists:' . __CLASS__ . '->' . $method);
        }
    }

}
