const panel = document.querySelector('[data-suggestions]');

if (panel) {
    const form = panel.closest('form');
    const fields = ['branch_id', 'room', 'day_of_week', 'start_period', 'end_period'];
    const expertise = panel.querySelector('#expertise');
    const button = panel.querySelector('[data-find-suggestions]');
    const status = panel.querySelector('[data-suggestion-status]');
    const results = panel.querySelector('[data-suggestion-results]');
    let activeRequest;

    panel.hidden = false;

    function clearSuggestions() {
        activeRequest?.abort();
        activeRequest = undefined;
        button.disabled = false;
        results.replaceChildren();
        status.textContent = '';
    }

    function appendOptions(title, options) {
        if (!options.length) {
            return;
        }

        const heading = document.createElement('h3');
        heading.textContent = title;
        const list = document.createElement('ul');
        list.className = 'suggestion-list';

        for (const option of options) {
            const item = document.createElement('li');
            const detail = document.createElement('div');
            const label = document.createElement('strong');
            label.textContent = option.full_name + ' · ' + option.period_label;
            const reason = document.createElement('p');
            reason.textContent = option.reason;
            detail.append(label, reason);
            const select = document.createElement('button');
            select.type = 'button';
            select.className = 'button button-secondary';
            select.textContent = 'Áp dụng';
            select.setAttribute('aria-label', 'Áp dụng ' + label.textContent);
            select.addEventListener('click', () => {
                form.elements.teacher_id.value = option.teacher_id;
                form.elements.start_period.value = option.start_period;
                form.elements.end_period.value = option.end_period;
                clearSuggestions();
                status.textContent = 'Đã chọn ' + label.textContent + '. Bấm Lưu lịch dạy để xác nhận.';
                button.focus();
            });
            item.append(detail, select);
            list.append(item);
        }
        results.append(heading, list);
    }

    for (const field of fields) {
        form.elements[field].addEventListener('input', clearSuggestions);
    }
    expertise.addEventListener('change', clearSuggestions);

    button.addEventListener('click', async () => {
        clearSuggestions();
        const url = new URL(panel.dataset.suggestions, window.location.origin);

        if (!expertise.value) {
            status.textContent = 'Hãy chọn chuyên môn cần tìm.';
            expertise.focus();
            return;
        }
        url.searchParams.set('expertise', expertise.value);

        for (const field of fields) {
            const input = form.elements[field];
            if (!input.value.trim() || !input.reportValidity()) {
                status.textContent = 'Hãy nhập cơ sở, phòng, ngày và khoảng tiết.';
                input.focus();
                return;
            }
            url.searchParams.set(field, input.value.trim());
        }
        if (Number(form.elements.end_period.value) < Number(form.elements.start_period.value)) {
            status.textContent = 'Tiết kết thúc phải bằng hoặc sau tiết bắt đầu.';
            form.elements.end_period.focus();
            return;
        }
        if (panel.dataset.schedule) {
            url.searchParams.set('exclude', panel.dataset.schedule);
        }

        const request = new AbortController();
        activeRequest = request;
        button.disabled = true;
        status.textContent = 'Đang tìm lịch phù hợp…';

        try {
            const response = await fetch(url, {headers: {Accept: 'application/json'}, signal: request.signal});
            if (!response.ok) {
                throw new Error('Schedule suggestion request failed.');
            }
            const data = await response.json();
            if (activeRequest !== request) {
                return;
            }
            status.textContent = data.room_busy
                ? 'Phòng đã có lớp trong khoảng tiết này.'
                : data.exact.length
                    ? 'Đã tìm thấy giảng viên phù hợp với khung tiết đã chọn.'
                    : 'Chưa có giảng viên phù hợp với khung tiết đã chọn.';
            if (!data.alternatives.length && !data.exact.length) {
                status.textContent += ' Hãy thử ngày hoặc phòng khác.';
            }
            appendOptions('Giảng viên phù hợp với khung tiết đã chọn', data.exact);
            appendOptions('Khung tiết khác trong cùng ngày', data.alternatives);
        } catch {
            if (activeRequest === request) {
                status.textContent = 'Chưa lấy được gợi ý. Hãy kiểm tra các ô hoặc tải lại trang nếu phiên đăng nhập đã hết hạn.';
            }
        } finally {
            if (activeRequest === request) {
                activeRequest = undefined;
                button.disabled = false;
            }
        }
    });
}
