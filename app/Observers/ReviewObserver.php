<?php

namespace App\Observers;

use App\Models\Action;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewObserver
{
    /**
     * Handle the review "creating" event.
     *
     * @param Review $review
     * @return void
     */
    public function creating(Review $review)
    {
        $review->user_id = request()->user()->id;
        $review->created_at = $review->freshTimestamp();
    }

    /**
     * Handle the review "created" event.
     *
     * @param Review $review
     * @return void
     */
    public function created(Review $review)
    {
        # получателю отзыва увеличиваем рейтинг
        User::whereKey($review->recipient_id)
            ->update([
                'reviews_count' => DB::raw('reviews_count + 1'),
                'scores_count'  => DB::raw("scores_count + {$review->scores}"),
            ]);

        # добавляем действие "Создан отзыв"
        $this->addAction($review, Action::REVIEW_CREATED);
    }

    /**
     * Handle the review "updating" event.
     *
     * @param Review $review
     * @return void
     */
    public function updating(Review $review)
    {
        $review->updated_at = $review->freshTimestamp();
    }

    /**
     * Handle the review "updated" event.
     *
     * @param Review $review
     * @return void
     */
    public function updated(Review $review)
    {
        $this->addAction($review, Action::REVIEW_UPDATES);
    }

    /**
     * Handle the review "deleted" event.
     *
     * @param Review $review
     * @return void
     */
    public function deleted(Review $review)
    {
        $this->addAction($review, Action::REVIEW_DELETED);
    }

    /**
     * Handle the review "force deleted" event.
     *
     * @param Review $review
     * @return void
     */
    public function forceDeleted(Review $review)
    {
        $this->addAction($review, Action::REVIEW_DELETED);
    }

    /**
     * Add action for user's model.
     *
     * @param Review $review
     * @param string $name
     */
    private function addAction(Review $review, string $name)
    {
        $auth_user_id = request()->user()->id ?? 0;

        Action::create([
            'user_id'  => $review->user_id,
            'is_owner' => $auth_user_id == $review->user_id,
            'name'     => $name,
            'changed'  => $review->getChanges(),
            'data'     => $review,
        ]);
    }
}
