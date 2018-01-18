<?php
namespace Talis\Persona\Client;

abstract class ValidationErrors
{
    const InvalidSignature = 0;
    const InvalidPublicKey = 1;
    const InvalidToken = 2;
    const EmptyResponse = 3;
    const Unknown = 4;
    const Unauthorised = 5;
}
