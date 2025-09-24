define("local_da_courses/qasync", ["core/ajax", "core/notification"], function (Ajax, Notification) {
    "use strict";
    function getUncheckedIds(container) {
        var out = [];
        container.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            if (cb && cb.checked === false) {
                var id = parseInt(cb.dataset.qaid, 10);
                if (id) out.push(id);
            }
        });
        return out.join("+");
    }
    function applyUpdates(container, updated) {
        updated.forEach(function (row) {
            var li = container.querySelector('li.qa-item[data-qaid="' + row.id + '"]');
            if (!li) return;
            var q = li.querySelector(".qa-qtext");
            var a = li.querySelector(".qa-atext");
            if (q) q.textContent = row.qtext;
            if (a) a.innerHTML = row.atext.replace(/\n/g, "<br>");
        });
    }
    function onSyncClick(btn) {
        // if (!courseId) return;
        // var container = btn.closest('[data-courseid="' + courseId + '"]') || btn.closest(".course-block");
        // if (!container) container = document;
        var courseId = parseInt(btn.dataset.courseid, 10);
        var fileid = parseInt(btn.dataset.fileid, 10);
        var container = btn.closest(".qa-list");
        var qaids = getUncheckedIds(container);
        if (!qaids) return false;
        btn.disabled = true;
        btn.classList.add("disabled");
        var req = Ajax.call([{ methodname: "local_da_courses_sync_questions", args: { courseid: courseId, fileid: fileid, qaids: qaids } }])[0];
        req.then(function (res) {
            // applyUpdates(container, res.updated || []);
            btn.disabled = false;
            btn.classList.remove("disabled");
            btn.textContent = "Synced (" + (res.count || 0) + ")";
            setTimeout(function () {
                btn.textContent = "Sync not approved";
            }, 1500);
        }).catch(function (e) {
            btn.disabled = false;
            btn.classList.remove("disabled");
            Notification.exception(e);
        });
    }
    function init() {
        document.querySelectorAll(".qa-regenerate").forEach(function (btn) {
            btn.addEventListener("click", function () {
                onSyncClick(btn);
            });
        });
    }
    return { init: init };
});
