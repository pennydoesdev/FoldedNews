<?php

namespace FoldedNews\Newsroom;

interface Module
{
    /**
     * Wire WordPress hooks. Called on `plugins_loaded`.
     */
    public function register(): void;
}
