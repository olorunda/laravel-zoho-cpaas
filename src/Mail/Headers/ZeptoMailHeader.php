<?php

namespace Olorunda\ZohoCpaas\Mail\Headers;

class ZeptoMailHeader
{
    public const TEMPLATE_KEY = 'X-Zepto-Template-Key';
    public const TEMPLATE_ALIAS = 'X-Zepto-Template-Alias';
    public const MAILAGENT_ALIAS = 'X-Zepto-Mailagent-Alias';
    public const MERGE_INFO = 'X-Zepto-Merge-Info';
    public const CLIENT_REFERENCE = 'X-Zepto-Client-Reference';
    public const BOUNCE_ADDRESS = 'X-Zepto-Bounce-Address';
    public const TRACK_CLICKS = 'X-Zepto-Track-Clicks';
    public const TRACK_OPENS = 'X-Zepto-Track-Opens';
}
