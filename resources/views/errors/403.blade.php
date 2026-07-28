@extends('errors.layout', [
    'code' => 403,
    'title' => 'This area is protected.',
    'message' => 'Your account does not have permission to open this page. Please use the correct dashboard for your role.',
])
