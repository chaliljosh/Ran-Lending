// Help & FAQ — accordion toggle for question/answer items.
// Only one answer stays open at a time; opening a new one closes the rest.

window.toggleFaq = function (btn) {
    const item = btn.closest('.faq-item');
    const answer = item.querySelector('.faq-answer');
    const isOpen = btn.classList.contains('is-open');

    document.querySelectorAll('.faq-question.is-open').forEach(openBtn => {
        openBtn.classList.remove('is-open');
        openBtn.closest('.faq-item').querySelector('.faq-answer').classList.remove('is-open');
    });

    if (!isOpen) {
        btn.classList.add('is-open');
        answer.classList.add('is-open');
    }
};