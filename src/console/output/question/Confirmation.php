<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\console\output\question;

use nb\console\output\Question;

class Confirmation extends Question {

    private $trueAnswerRegex;

    /**
     * 构造方法
     * @param string $question 问题
     * @param bool $default 默认答案
     * @param string $trueAnswerRegex 验证正则
     */
    public function __construct($question, $default = true, $trueAnswerRegex = '/^y/i') {
        parent::__construct($question, (bool)$default);

        $this->trueAnswerRegex = $trueAnswerRegex;
        $this->setNormalizer($this->getDefaultNormalizer());
    }

    /**
     * 获取默认的答案回调
     * @return callable
     */
    private function getDefaultNormalizer() {
        $default = $this->getDefault();
        $regex = $this->trueAnswerRegex;

        return function ($answer) use ($default, $regex) {
            if (is_bool($answer)) {
                return $answer;
            }

            $answerIsTrue = (bool)preg_match($regex, $answer);
            if (false === $default) {
                return $answer && $answerIsTrue;
            }

            return !$answer || $answerIsTrue;
        };
    }
}
