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
    case ARRAY_OF_STRINGS;
    case ARRAY_OF_OBJECTS;
    case ARRAY_OF_BOOLEANS;
    case ARRAY_OF_INTEGERS;
    case ARRAY_OF_FLOATS;
    case ARRAY_OF_DATES;
}