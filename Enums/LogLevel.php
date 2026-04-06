<?php

enum LogLevel: string
{
    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case NOTICE = 'NOTICE';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';
    case ALERT = 'ALERT';
    case EMERGENCY = 'EMERGENCY';
}