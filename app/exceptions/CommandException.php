<?php
/**
 * 커맨드 Exception
 */
class CommandException extends Exception implements Exceptions
{
    protected $hint;

    public function __construct($message = "", $code = 0, $hint = "")
    {
        parent::__construct($message, $code);
        $this->display();
    }

    public function display()
    {
        $trace = $this->getTrace();

        printf("\n%s \n\nfile: %s\nline: %s\nclass: %s\nfunction: %s\n\n", $this->message, $trace[0]['file'], $trace[0]['line'], $trace[0]['class'], $trace[0]['function']);
        $this->save();
    }

    // TODO :: DB에 저장하든 파일로 떨구던 로깅을 해야한다. 크론일수도 있으니까.
    public function save()
    {
        return false;
    }
}