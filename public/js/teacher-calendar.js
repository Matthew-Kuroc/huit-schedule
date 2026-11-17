const lessonDialog = document.getElementById('lesson-details');

if (lessonDialog) {
    const detailFields = {
        title: 'subject',
        date: 'date',
        period: 'period',
        room: 'room',
        branch: 'branch',
        address: 'address',
    };

    document.querySelector('.teacher-calendar').addEventListener('click', (event) => {
        const lesson = event.target.closest('.calendar-lesson');

        if (!lesson) {
            return;
        }

        for (const [field, attribute] of Object.entries(detailFields)) {
            document.getElementById(`lesson-${field}`).textContent = lesson.dataset[attribute];
        }

        lessonDialog.showModal();
    });
}
