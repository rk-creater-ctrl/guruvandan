const setExpandedNavigation = () => {
    const toggle = document.querySelector(".nav-toggle");
    const navigation = document.getElementById("main-navigation");
    if (!toggle || !navigation) return;
    toggle.addEventListener("click", () => {
        const expanded = toggle.getAttribute("aria-expanded") === "true";
        toggle.setAttribute("aria-expanded", String(!expanded));
        navigation.classList.toggle("is-open", !expanded);
    });
};

const renderCountdowns = () => {
    document.querySelectorAll("[data-countdown]").forEach((countdown) => {
        const target = new Date(countdown.dataset.countdown).getTime();
        const end = countdown.dataset.countdownEnd
            ? new Date(countdown.dataset.countdownEnd).getTime()
            : target + 4 * 60 * 60 * 1000;
        const valueNode = countdown.querySelector("strong") || countdown;
        const render = () => {
            const now = Date.now();
            if (now >= end) {
                valueNode.textContent = "The Guru Purnima celebration has concluded.";
                return;
            }
            if (now >= target) {
                valueNode.textContent = "The Guru Purnima celebration is live.";
                return;
            }
            const diff = target - now;
            const days = Math.floor(diff / 86400000);
            const hours = Math.floor((diff / 3600000) % 24);
            const minutes = Math.floor((diff / 60000) % 60);
            valueNode.textContent = `${days}d ${hours}h ${minutes}m until the celebration`;
        };
        render();
        window.setInterval(render, 60000);
    });
};

const setupUploadPreviews = () => {
    document.querySelectorAll("[data-upload-form]").forEach((form) => {
        const input = form.querySelector("[data-file-input]");
        const preview = form.querySelector("[data-file-preview]");
        const remove = form.querySelector("[data-remove-file]");
        const progress = form.querySelector("[data-upload-progress]");
        if (!input || !preview || !remove) return;

        const clear = () => {
            input.value = "";
            preview.replaceChildren();
            preview.hidden = true;
            remove.hidden = true;
        };
        input.addEventListener("change", () => {
            const file = input.files?.[0];
            if (!file) return clear();
            preview.replaceChildren();
            const description = document.createElement("p");
            description.textContent = `${file.name} - ${(file.size / 1024 / 1024).toFixed(2)} MB`;
            preview.append(description);
            if (file.type.startsWith("image/")) {
                const image = document.createElement("img");
                image.src = URL.createObjectURL(file);
                image.alt = "Selected upload preview";
                image.onload = () => URL.revokeObjectURL(image.src);
                preview.prepend(image);
            } else if (file.type.startsWith("audio/")) {
                const audio = document.createElement("audio");
                audio.controls = true;
                audio.src = URL.createObjectURL(file);
                preview.prepend(audio);
            } else if (file.type.startsWith("video/")) {
                const video = document.createElement("video");
                video.controls = true;
                video.preload = "metadata";
                video.src = URL.createObjectURL(file);
                preview.prepend(video);
            }
            preview.hidden = false;
            remove.hidden = false;
        });
        remove.addEventListener("click", clear);
        form.addEventListener("submit", () => {
            if (progress && input.files?.length) {
                progress.hidden = false;
                progress.removeAttribute("value");
            }
        });
    });
};

const setupAiAssistant = () => {
    const output = document.getElementById("ai-output");
    const generate = document.getElementById("generate-message");
    const retry = document.getElementById("retry-message");
    const teacherSelect = document.getElementById("ai-teacher-select");
    if (!output || !generate || !teacherSelect) return;

    const syncTeacher = () => {
        const selected = teacherSelect.selectedOptions[0];
        document.getElementById("ai-teacher").value = selected?.dataset.name || "";
    };
    syncTeacher();
    teacherSelect.addEventListener("change", syncTeacher);

    const requestDraft = async () => {
        const experience = document.getElementById("ai-memory").value.trim();
        if (!experience) {
            output.value = "Please add one memorable experience first. Specific moments create warmer, more personal drafts.";
            document.getElementById("ai-memory").focus();
            return;
        }
        generate.disabled = true;
        generate.textContent = "Generating...";
        retry.hidden = true;
        output.value = "Creating your tribute draft...";
        try {
            const response = await fetch(window.guruVandan.aiEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": window.guruVandan.csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    teacher_id: teacherSelect.value,
                    teacher_name: document.getElementById("ai-teacher").value,
                    experience,
                    language: document.getElementById("ai-language").value,
                    content_type: document.getElementById("ai-content-type").value,
                    desired_length: document.getElementById("ai-length").value,
                }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || "The writing assistant is temporarily unavailable.");
            output.value = payload.content;
        } catch (error) {
            output.value = `${error.message} You can retry, or continue writing manually without losing your form.`;
            retry.hidden = false;
        } finally {
            generate.disabled = false;
            generate.textContent = "Generate draft";
        }
    };
    generate.addEventListener("click", requestDraft);
    retry?.addEventListener("click", requestDraft);
    document.getElementById("copy-ai-output")?.addEventListener("click", async () => {
        await navigator.clipboard.writeText(output.value);
    });
    document.getElementById("insert-ai-output")?.addEventListener("click", () => {
        const message = document.getElementById("tribute-message");
        message.value = output.value;
        message.focus();
        message.scrollIntoView({ behavior: "smooth", block: "center" });
    });
};

const setupTeacherSync = () => {
    const tributeTeacher = document.getElementById("tribute-teacher");
    const aiTeacher = document.getElementById("ai-teacher-select");
    tributeTeacher?.addEventListener("change", () => {
        if (!aiTeacher) return;
        aiTeacher.value = tributeTeacher.value;
        aiTeacher.dispatchEvent(new Event("change"));
    });
};

const setupDialogs = () => {
    document.querySelectorAll("[data-edit-schedule]").forEach((button) => {
        button.addEventListener("click", () => document.getElementById(`schedule-dialog-${button.dataset.editSchedule}`)?.showModal());
    });
    document.querySelectorAll("[data-close-dialog]").forEach((button) => {
        button.addEventListener("click", () => button.closest("dialog")?.close());
    });
    document.querySelectorAll("[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
};

const setupTeacherMessagePopup = () => {
    const dialog = document.querySelector("[data-teacher-message-popup]");
    if (!dialog) return;

    const storageKey = dialog.dataset.popupKey || "teacher-message-popup";
    if (window.sessionStorage.getItem(storageKey)) return;

    window.setTimeout(() => {
        if (typeof dialog.showModal === "function") dialog.showModal();
        else dialog.setAttribute("open", "");
        window.sessionStorage.setItem(storageKey, "shown");
    }, 650);
};

const setupSubmitGuards = () => {
    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", (event) => {
            window.setTimeout(() => {
                if (event.defaultPrevented) return;
                form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
                    button.disabled = true;
                    button.dataset.originalText = button.textContent;
                    button.textContent = button.dataset.submittingText || "Please wait...";
                });
            }, 0);
        });
    });
};

const setupLightbox = () => {
    const triggers = document.querySelectorAll("[data-lightbox-src]");
    if (!triggers.length) return;
    const dialog = document.createElement("dialog");
    dialog.className = "lightbox-dialog";
    dialog.innerHTML = '<button type="button" aria-label="Close image preview">Close</button><img alt="Full-size tribute media">';
    document.body.append(dialog);
    dialog.querySelector("button").addEventListener("click", () => dialog.close());
    dialog.addEventListener("click", (event) => {
        if (event.target === dialog) dialog.close();
    });
    triggers.forEach((trigger) => trigger.addEventListener("click", () => {
        dialog.querySelector("img").src = trigger.dataset.lightboxSrc;
        dialog.showModal();
    }));
};

const setupBulkSelection = () => {
    document.querySelector("[data-select-all]")?.addEventListener("change", (event) => {
        document.querySelectorAll('input[name="tribute_ids[]"]').forEach((checkbox) => {
            checkbox.checked = event.target.checked;
        });
    });
};

const setupShare = () => {
    document.querySelectorAll("[data-share-url]").forEach((button) => {
        button.addEventListener("click", async () => {
            if (navigator.share) await navigator.share({ title: "GuruVandan Tribute", url: button.dataset.shareUrl });
            else await navigator.clipboard.writeText(button.dataset.shareUrl);
        });
    });
};

const setupPasswordToggles = () => {
    document.querySelectorAll("[data-toggle-password]").forEach((button) => {
        button.addEventListener("click", () => {
            const input = button.closest(".password-row")?.querySelector("[data-password-input]");
            if (!input) return;
            const visible = input.type === "text";
            input.type = visible ? "password" : "text";
            button.textContent = visible ? "Show" : "Hide";
        });
    });
};

setExpandedNavigation();
renderCountdowns();
setupUploadPreviews();
setupAiAssistant();
setupTeacherSync();
setupDialogs();
setupTeacherMessagePopup();
setupSubmitGuards();
setupLightbox();
setupBulkSelection();
setupShare();
setupPasswordToggles();
