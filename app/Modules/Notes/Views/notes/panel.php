<section class="card mt-4" id="notes">
<div class="card-head"><div><span class="eyebrow"><?= icon('edit') ?> My notes</span><h3 class="mt-1">Notes for this lesson</h3></div><small class="muted nowrap" data-note-status aria-live="polite"><?= $note ? 'Saved ' . e(time_ago($note['updated_at'])) : 'Private to you' ?></small></div>
<textarea class="note-area" rows="5" maxlength="20000" placeholder="Write key takeaways, ideas or questions… (saves automatically)" data-note="<?= e(url('/notes/' . $lesson['id'])) ?>" aria-label="Lesson notes"><?= e($note['body'] ?? '') ?></textarea>
<div class="row between mt-2 small"><span class="muted">Only you can see these notes.</span><a href="<?= e(url('/notes')) ?>">All my notes <?= icon('arrow-right') ?></a></div>
</section>
