<?php

namespace lib\components;

/**
 * Component that provides a queue of events that will be transported
 * to the browser. The actual transport will be handled by the
 * application (either simply piggy-backing on the response or 
 * by implementing a "real" idependent event transport).
 */
class EventQueue extends \yii\base\Component
{
  /**
   * The event queue
   *
   * @var \yii\base\Event[]
   */
  protected $events = [];

  /**
   * Add an event to the queue.   
   *
   * @param \yii\base\Event $event
   * @return void
   */
  public function add( \yii\base\Event $event )
  {
    //\Yii::debug("Event:" . $event->name, __METHOD__);
    $this->events[] = $event;
  }

  /**
   * Returns true if there are events to be transported
   *
   * @return boolean
   */
  public function hasEvents()
  {
    return count($this->events) >0;
  }

  /**
   * Converts the event queue to an array of arrays containing
   * the event properties. The conversion is done in the [[EventQueue::_eventToArray]]
   * method, which can be overridden in subclasses.
   *
   * @return array
   */
  public function toArray()
  {
    return array_map( [$this, "_eventToArray" ], $this->events );
  }

  /**
   * Empties the event queue
   */
  public function clean(){
    $this->events = [];
  }

  /**
   * Converts a single event into an array
   *
   * @param \yii\base\Event $event
   * @return array
   */
  protected function _eventToArray( \yii\base\Event $event )
  {
    return [
      'name' => $event->name,
      'data' => $event->data
    ];
  }
}