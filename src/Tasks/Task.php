<?php

namespace Armie\Tasks;

use Armie\Dto\TaskDto;
use Armie\Interfaces\Runnable;

/**
 * Define tasks operation
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @template T
 */
abstract class Task implements Runnable
{
  private string $name;

  public function __construct(string $name = null)
  {
    $this->name = ($name ?: static::class) . ":" . microtime(true) . ":" . bin2hex(random_bytes(8));
  }

  /**
   * Implementation of magic method __invoke()
   */
  public function __invoke()
  {
    return $this->run();
  }

  /**
   * Get task  name
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Convert to task request
   * 
   * @param bool $async - Async request
   * @return TaskDto
   */
  public function getRequest(bool $async = true): TaskDto
  {
    return (new TaskDto)
      ->setName($this->getName())
      ->setAsync($async)
      ->setClass(static::class)
      ->setParams($this->getParams());
  }

  /**
   * Get task params - Params passed into task's constructor
   */
  abstract function getParams(): array;

  /**
   * @return T
   */
  abstract public function run();
}
