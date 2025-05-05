<?php

use Hacon\ThemeCore\ThemeModules\PageAutoTableOfContetns\PageAutoTableOfContetns;

function getPageTableOfContentsData()
{
    return PageAutoTableOfContetns::getInstance()->getTableOfContentsData();
}