<?php
namespace App\Services;

use Models\Review;

class ReviewService {

    public function store(array $content): Review
    {
        $user = Review::insert($content);

        return $content;
    }
