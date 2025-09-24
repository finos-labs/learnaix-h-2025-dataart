/**
 * Inline edit for Q/A list.
 * @module local_da_courses/qaedit
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    function startEdit(li) {
        const qSpan = li.querySelector('.qa-qtext');
        const aSpan = li.querySelector('.qa-atext');
        const oldQ  = qSpan ? qSpan.textContent : '';
        const oldA  = aSpan ? aSpan.textContent : '';

        // Replace spans with textareas.
        const qTa = document.createElement('textarea');
        qTa.className = 'form-control qa-qta';
        qTa.value = oldQ;

        const aTa = document.createElement('textarea');
        aTa.className = 'form-control qa-ata';
        aTa.value = oldA;

        qSpan.replaceWith(qTa);
        aSpan.replaceWith(aTa);

        // Replace actions with Save/Cancel.
        const actions = li.querySelector('.qa-actions');
        actions.innerHTML = '';

        const save = document.createElement('button');
        save.className = 'btn btn-primary qa-save';
        save.textContent = 'Save';

        const cancel = document.createElement('button');
        cancel.className = 'btn btn-secondary ml-1 qa-cancel';
        cancel.textContent = 'Cancel';

        actions.append(save, cancel);

        save.addEventListener('click', function() {
            doSave(li, qTa.value, aTa.value);
        });
        cancel.addEventListener('click', function() {
            cancelEdit(li, qTa, aTa, oldQ, oldA);
        });
    }

    function cancelEdit(li, qTa, aTa, oldQ, oldA) {
        const qSpan = document.createElement('span');
        qSpan.className = 'qa-qtext';
        qSpan.textContent = oldQ;

        const aSpan = document.createElement('span');
        aSpan.className = 'qa-atext';
        aSpan.textContent = oldA;

        qTa.replaceWith(qSpan);
        aTa.replaceWith(aSpan);

        const actions = li.querySelector('.qa-actions');
        actions.innerHTML = '<button class="btn btn-secondary qa-edit">Edit</button>';
        actions.querySelector('.qa-edit').addEventListener('click', function() {
            startEdit(li);
        });
    }

    function doSave(li, qNew, aNew) {
        const id = parseInt(li.dataset.qaid, 10);
        const req = Ajax.call([{
            methodname: 'local_da_courses_update_qa',
            args: { id: id, qtext: qNew, atext: aNew }
        }])[0];

        req.then(function(res) {
            // Replace textareas with spans (updated).
            const qSpan = document.createElement('span');
            qSpan.className = 'qa-qtext';
            qSpan.textContent = res.qtext;

            const aSpan = document.createElement('span');
            aSpan.className = 'qa-atext';
            aSpan.textContent = res.atext;

            li.querySelector('.qa-qta').replaceWith(qSpan);
            li.querySelector('.qa-ata').replaceWith(aSpan);

            const actions = li.querySelector('.qa-actions');
            actions.innerHTML = '<span class="text-success">Saved</span> ' +
                                '<button class="btn btn-secondary ml-1 qa-edit">Edit</button>';
            actions.querySelector('.qa-edit').addEventListener('click', function() {
                startEdit(li);
            });
        }).catch(Notification.exception);
    }

    function init(opts) {
        // Attach once (event delegation is fine; here, bind current buttons).
        document.querySelectorAll('.qa-edit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const li = btn.closest('li[data-qaid]');
                if (li) startEdit(li);
            });
        });
    }

    return { init: init };
});
