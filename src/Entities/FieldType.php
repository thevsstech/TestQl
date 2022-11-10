<?php

namespace NovaTech\TestQL\Entities;

enum FieldType
{
    case INTEGER;
    case FLOAT;
    case STRING;
    case DATE;
    case BOOLEAN;
    case OBJECT;
    case ARRAY;
    case NULL;
}