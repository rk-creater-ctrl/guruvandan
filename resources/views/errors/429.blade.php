@extends('errors.layout', [
    'code' => 429,
    'title' => 'Please slow down for a moment.',
    'message' => 'Too many requests were sent in a short time. Please try again shortly.',
])
