(function () {
    'use strict';

    const BASE_URL = document.body?.dataset.baseUrl || '/';
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    let speechTimer = null;
    let speechRequestId = 0;

    if ('scrollRestoration' in window.history) {
        window.history.scrollRestoration = 'manual';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function parseJsonScript(id) {
        const el = document.getElementById(id);
        if (!el) return [];
        try {
            const data = JSON.parse(el.textContent || '[]');
            return Array.isArray(data) ? data : [];
        } catch (error) {
            console.error('Invalid JSON data:', id, error);
            return [];
        }
    }

    function shuffle(input) {
        const arr = [...input];
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
    }

    function uniqueValues(values) {
        const seen = new Set();
        return values.filter((value) => {
            const key = String(value ?? '').trim().toLowerCase();
            if (!key || seen.has(key)) return false;
            seen.add(key);
            return true;
        });
    }

    function normalizeAnswer(value) {
        return String(value ?? '').trim().toLowerCase().replace(/\s+/g, ' ');
    }

    function termForSpeech(value) {
        return String(value ?? '')
            .replace(/\s*\([^)]*\)/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function isTypingTarget(target) {
        if (!target) return false;
        const tagName = target.tagName ? target.tagName.toLowerCase() : '';
        return target.isContentEditable || ['input', 'textarea', 'select'].includes(tagName);
    }

    function isInteractiveTarget(target) {
        if (!target) return false;
        const tagName = target.tagName ? target.tagName.toLowerCase() : '';
        return isTypingTarget(target) || ['button', 'a', 'label'].includes(tagName);
    }

    function isSpaceKey(event) {
        return event.key === ' ' || event.key === 'Spacebar' || event.code === 'Space';
    }

    function boolSetting(key, defaultValue) {
        try {
            const value = localStorage.getItem(key);
            if (value === null) return defaultValue;
            return value === 'true';
        } catch (error) {
            return defaultValue;
        }
    }

    function setBoolSetting(key, value) {
        try {
            localStorage.setItem(key, value ? 'true' : 'false');
        } catch (error) {
            // localStorage can be disabled in private browsing. The app still works without persistence.
        }
    }

    function postJson(path, payload) {
        return fetch(BASE_URL + path.replace(/^\/+/, ''), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        }).then((response) => response.json()).catch(() => ({ success: false }));
    }

    function saveProgress(setId, cardId, mode, result, lastAnswer) {
        if (!setId || !cardId) return Promise.resolve({ success: false });
        return postJson('actions/save_progress.php', {
            action: 'progress',
            set_id: setId,
            card_id: cardId,
            mode,
            result,
            last_answer: lastAnswer || ''
        });
    }

    function saveTestResult(setId, score, totalQuestions, mode) {
        if (!setId || !totalQuestions) return Promise.resolve({ success: false });
        return postJson('actions/save_progress.php', {
            action: 'test_result',
            set_id: setId,
            score,
            total_questions: totalQuestions,
            mode
        });
    }

    function setupSidebar() {
        const toggle = $('#sidebarToggle');
        const sidebar = $('#sidebar');
        const backdrop = $('#sidebarBackdrop');
        if (!toggle || !sidebar || !backdrop) return;

        const close = () => {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        };

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        });
        backdrop.addEventListener('click', close);
    }

    function setupConfirmDelete() {
        $$('.confirm-delete').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const message = form.dataset.confirm || 'Bạn chắc chắn muốn xóa?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    function setupAlerts() {
        $$('.app-alert').forEach((alert) => {
            setTimeout(() => {
                if (window.bootstrap?.Alert) {
                    window.bootstrap.Alert.getOrCreateInstance(alert).close();
                } else {
                    alert.remove();
                }
            }, 4500);
        });
    }

    function setupDynamicCardRows() {
        const addButton = $('#addCardRow');
        const rows = $('#cardRows');
        if (!addButton || !rows) return;

        const bindRemove = (row) => {
            const removeButton = $('.remove-card-row', row);
            if (removeButton) {
                removeButton.addEventListener('click', () => {
                    if ($$('.card-row', rows).length > 1) {
                        row.remove();
                    } else {
                        $$('input, textarea', row).forEach((input) => {
                            input.value = '';
                        });
                    }
                });
            }
        };

        $$('.card-row', rows).forEach(bindRemove);

        addButton.addEventListener('click', () => {
            const first = $('.card-row', rows);
            if (!first) return;
            const clone = first.cloneNode(true);
            $$('input, textarea', clone).forEach((input) => {
                input.value = '';
            });
            rows.appendChild(clone);
            bindRemove(clone);
        });
    }

    function setupVisibilityControls() {
        $$('.visibility-select').forEach((select) => {
            const target = select.dataset.classTarget ? document.querySelector(select.dataset.classTarget) : null;
            if (!target) return;

            const sync = () => {
                const showClass = select.value === 'class';
                target.hidden = !showClass;
                $$('select, input, textarea', target).forEach((input) => {
                    input.disabled = !showClass;
                });
            };

            select.addEventListener('change', sync);
            sync();
        });
    }

    function stopSpeaking() {
        speechRequestId += 1;
        if (speechTimer) {
            clearTimeout(speechTimer);
            speechTimer = null;
        }
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            if (window.speechSynthesis.paused) {
                window.speechSynthesis.resume();
            }
        }
    }

    function speakText(text) {
        const notice = $('#speechNotice');

        if (!('speechSynthesis' in window) || !window.SpeechSynthesisUtterance) {
            if (notice) notice.hidden = false;
            return;
        }

        const cleanText = String(text ?? '').trim();
        if (!cleanText) return;

        speechRequestId += 1;
        const requestId = speechRequestId;

        if (speechTimer) {
            clearTimeout(speechTimer);
            speechTimer = null;
        }

        const synth = window.speechSynthesis;
        synth.cancel();
        if (synth.paused) {
            synth.resume();
        }

        if (requestId !== speechRequestId) return;

        let started = false;
        if (notice) notice.hidden = true;

        function makeUtterance() {
            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.lang = 'en-US';
            utterance.rate = 0.92;
            utterance.pitch = 1;

            const voices = synth.getVoices ? synth.getVoices() : [];
            const englishVoice = voices.find((voice) => voice.lang === 'en-US') || voices.find((voice) => /^en[-_]/i.test(voice.lang));
            if (englishVoice) {
                utterance.voice = englishVoice;
            }

            utterance.onstart = () => {
                started = true;
                if (notice) notice.hidden = true;
            };
            utterance.onend = () => {
                if (requestId === speechRequestId && synth.paused) {
                    synth.resume();
                }
            };
            utterance.onerror = (event) => {
                if (requestId !== speechRequestId || ['canceled', 'interrupted'].includes(event.error)) {
                    return;
                }
                if (synth.paused) {
                    synth.resume();
                }
            };

            return utterance;
        }

        speechTimer = setTimeout(() => {
            speechTimer = null;
            if (requestId !== speechRequestId) return;

            try {
                synth.speak(makeUtterance());
                if (synth.paused) {
                    synth.resume();
                }
            } catch (error) {
                if (notice) {
                    notice.textContent = 'Không thể phát âm lúc này. Hãy bấm loa hoặc thử lại một lần nữa.';
                    notice.hidden = false;
                }
                return;
            }

            window.setTimeout(() => {
                if (requestId !== speechRequestId || started || synth.speaking) return;
                try {
                    synth.cancel();
                    synth.speak(makeUtterance());
                    if (synth.paused) {
                        synth.resume();
                    }
                } catch (error) {
                    if (notice) {
                        notice.textContent = 'Không thể phát âm lúc này. Hãy bấm loa hoặc thử lại một lần nữa.';
                        notice.hidden = false;
                    }
                }
            }, 520);
        }, 70);
    }

    window.speakText = speakText;
    window.stopSpeaking = stopSpeaking;

    function setupFlashcards() {
        const app = $('#flashcardApp');
        if (!app) return;

        const cards = parseJsonScript('flashcardData');
        if (!cards.length) return;

        const setId = Number(app.dataset.setId || 0);
        const state = {
            index: 0,
            flipped: false,
            termReadOnFront: false,
            autoRunning: false,
            completed: false,
            settings: {
                autoSpeak: boolSetting('flashcard_auto_speak', false),
                speakerEnabled: boolSetting('flashcard_speaker_enabled', true),
                speakExample: boolSetting('flashcard_speak_example', true),
                showIpa: boolSetting('flashcard_show_ipa', true)
            }
        };

        const cardEl = $('#flashcard');
        const counter = $('#flashcardCounter');
        const progress = $('#flashcardProgress');
        const termEl = $('#fcTerm');
        const defEl = $('#fcDefinition');
        const pronFront = $('#fcPronunciationFront');
        const pronBack = $('#fcPronunciationBack');
        const exampleEl = $('#fcExample');
        const exampleButton = $('.fc-speak-example');
        const autoToggle = $('#flashcardAutoSpeak');
        const speakerToggle = $('#flashcardSpeakerEnabled');
        const exampleToggle = $('#flashcardSpeakExample');
        const ipaToggle = $('#flashcardShowIpa');
        const autoRunButton = $('#fcAutoRun');
        const completeEl = $('#flashcardComplete');
        const restartButton = $('#fcRestart');
        let autoRunTimer = null;

        function updatePronunciation(card) {
            [pronFront, pronBack].forEach((el) => {
                if (!el) return;
                if (state.settings.showIpa && card?.pronunciation) {
                    el.textContent = card.pronunciation;
                    el.hidden = false;
                } else {
                    el.textContent = '';
                    el.hidden = true;
                }
            });
        }

        function syncSettings() {
            if (autoToggle) autoToggle.checked = state.settings.autoSpeak;
            if (speakerToggle) speakerToggle.checked = state.settings.speakerEnabled;
            if (exampleToggle) exampleToggle.checked = state.settings.speakExample;
            if (ipaToggle) ipaToggle.checked = state.settings.showIpa;
            $$('.speaker-btn').forEach((button) => {
                button.hidden = !state.settings.speakerEnabled;
                button.disabled = !state.settings.speakerEnabled;
            });
            if (exampleButton) {
                exampleButton.hidden = !state.settings.speakExample || !cards[state.index]?.example_sentence;
                exampleButton.disabled = !state.settings.speakExample;
            }
        }

        function hideCompletion() {
            state.completed = false;
            if (completeEl) completeEl.hidden = true;
        }

        function render(shouldSpeak) {
            const card = cards[state.index];
            if (!card) return;
            hideCompletion();
            state.flipped = false;
            state.termReadOnFront = false;
            cardEl?.classList.remove('flipped');
            termEl.textContent = card.term || '';
            defEl.textContent = card.definition || '';
            exampleEl.textContent = card.example_sentence || '';

            updatePronunciation(card);

            if (counter) counter.textContent = `${state.index + 1}/${cards.length}`;
            if (progress) progress.style.width = `${((state.index + 1) / cards.length) * 100}%`;
            syncSettings();

            if (shouldSpeak && state.settings.autoSpeak) {
                speakText(termForSpeech(card.term));
            }
        }

        function syncAutoRunButton() {
            if (!autoRunButton) return;
            autoRunButton.classList.toggle('is-running', state.autoRunning);
            autoRunButton.setAttribute('aria-pressed', state.autoRunning ? 'true' : 'false');
            autoRunButton.setAttribute('aria-label', state.autoRunning ? 'Dừng tự động chạy' : 'Tự động chạy');
            autoRunButton.setAttribute('title', state.autoRunning ? 'Dừng tự động chạy' : 'Tự động chạy');
            autoRunButton.innerHTML = state.autoRunning ? '<i class="bi bi-stop-fill"></i>' : '<i class="bi bi-play-fill"></i>';
        }

        function clearAutoRunTimer() {
            if (autoRunTimer) {
                clearTimeout(autoRunTimer);
                autoRunTimer = null;
            }
        }

        function stopAutoRun(shouldStopSpeaking = true) {
            state.autoRunning = false;
            clearAutoRunTimer();
            syncAutoRunButton();
            if (shouldStopSpeaking) {
                stopSpeaking();
            }
        }

        function scheduleAutoRun(callback, delay) {
            clearAutoRunTimer();
            autoRunTimer = setTimeout(() => {
                autoRunTimer = null;
                callback();
            }, delay);
        }

        function showCompletion() {
            state.completed = true;
            stopAutoRun();
            stopSpeaking();
            if (counter) counter.textContent = `${cards.length}/${cards.length}`;
            if (progress) progress.style.width = '100%';
            if (completeEl) {
                completeEl.hidden = false;
                completeEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function restartFlashcards() {
            stopAutoRun();
            stopSpeaking();
            state.index = 0;
            state.completed = false;
            render(true);
            cardEl?.focus({ preventScroll: true });
        }

        function runCurrentCardAutomatically() {
            if (!state.autoRunning) return;

            const card = cards[state.index];
            if (!card) {
                stopAutoRun();
                return;
            }

            render(false);
            state.termReadOnFront = true;
            speakText(termForSpeech(card.term));

            scheduleAutoRun(() => {
                if (!state.autoRunning) return;

                state.flipped = true;
                cardEl?.classList.add('flipped');

                if (state.settings.speakExample && card.example_sentence) {
                    speakText(card.example_sentence);
                }

                const nextDelay = state.settings.speakExample && card.example_sentence ? 6200 : 4200;
                scheduleAutoRun(() => {
                    if (!state.autoRunning) return;

                    if (state.index >= cards.length - 1) {
                        showCompletion();
                        return;
                    }

                    state.index += 1;
                    render(false);
                    scheduleAutoRun(runCurrentCardAutomatically, 900);
                }, nextDelay);
            }, 3600);
        }

        function startAutoRun() {
            if (state.completed || (completeEl && !completeEl.hidden)) {
                state.index = 0;
            }
            hideCompletion();
            state.autoRunning = true;
            syncAutoRunButton();
            stopSpeaking();
            runCurrentCardAutomatically();
        }

        function move(delta) {
            stopAutoRun();
            stopSpeaking();
            if (delta > 0 && state.index >= cards.length - 1) {
                showCompletion();
                return;
            }
            state.index = (state.index + delta + cards.length) % cards.length;
            render(true);
        }

        function flipCard() {
            const card = cards[state.index];
            if (!card) return;

            if (state.settings.autoSpeak && !state.flipped && !state.termReadOnFront) {
                state.termReadOnFront = true;
                speakText(termForSpeech(card.term));
                return;
            }

            state.flipped = !state.flipped;
            cardEl.classList.toggle('flipped', state.flipped);

            if (state.flipped) {
                if (state.settings.autoSpeak && state.settings.speakExample && card.example_sentence) {
                    speakText(card.example_sentence);
                }
            } else {
                state.termReadOnFront = false;
                if (state.settings.autoSpeak) {
                    state.termReadOnFront = true;
                    speakText(termForSpeech(card.term));
                }
            }
        }

        function saveAndMove(result) {
            stopAutoRun();
            const card = cards[state.index];
            saveProgress(setId, card.id, 'flashcards', result, result === 'correct' ? card.term : '');
            move(1);
        }

        cardEl?.addEventListener('click', (event) => {
            if (event.target.closest('button')) return;
            stopAutoRun(false);
            flipCard();
        });
        cardEl?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || isSpaceKey(event)) {
                event.preventDefault();
                event.stopPropagation();
                stopAutoRun(false);
                flipCard();
            }
        });

        $('#fcPrev')?.addEventListener('click', () => move(-1));
        $('#fcNext')?.addEventListener('click', () => move(1));
        $('#fcKnow')?.addEventListener('click', () => saveAndMove('correct'));
        $('#fcWrong')?.addEventListener('click', () => saveAndMove('wrong'));
        restartButton?.addEventListener('click', restartFlashcards);
        autoRunButton?.addEventListener('click', () => {
            if (state.autoRunning) {
                stopAutoRun();
            } else {
                startAutoRun();
            }
        });
        $$('.fc-speak-term').forEach((button) => button.addEventListener('click', () => {
            stopAutoRun(false);
            state.termReadOnFront = true;
            speakText(termForSpeech(cards[state.index]?.term));
        }));
        exampleButton?.addEventListener('click', () => {
            stopAutoRun(false);
            speakText(cards[state.index]?.example_sentence);
        });

        autoToggle?.addEventListener('change', () => {
            state.settings.autoSpeak = autoToggle.checked;
            setBoolSetting('flashcard_auto_speak', state.settings.autoSpeak);
            if (state.settings.autoSpeak) {
                speakText(termForSpeech(cards[state.index]?.term));
            } else {
                stopSpeaking();
            }
        });
        speakerToggle?.addEventListener('change', () => {
            state.settings.speakerEnabled = speakerToggle.checked;
            setBoolSetting('flashcard_speaker_enabled', state.settings.speakerEnabled);
            syncSettings();
        });
        exampleToggle?.addEventListener('change', () => {
            state.settings.speakExample = exampleToggle.checked;
            setBoolSetting('flashcard_speak_example', state.settings.speakExample);
            syncSettings();
        });
        ipaToggle?.addEventListener('change', () => {
            state.settings.showIpa = ipaToggle.checked;
            setBoolSetting('flashcard_show_ipa', state.settings.showIpa);
            updatePronunciation(cards[state.index]);
        });

        if (!('speechSynthesis' in window)) {
            const notice = $('#speechNotice');
            if (notice) notice.hidden = false;
        }

        document.addEventListener('keydown', (event) => {
            if (!document.body.contains(app) || isTypingTarget(event.target)) {
                return;
            }

            const key = event.key.toLowerCase();
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                move(-1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                move(1);
            } else if (isSpaceKey(event) || event.key === 'Enter') {
                if (isInteractiveTarget(event.target)) return;
                event.preventDefault();
                stopAutoRun(false);
                flipCard();
            } else if (key === 'k' || key === '2') {
                event.preventDefault();
                saveAndMove('correct');
            } else if (key === 'd' || key === '1') {
                event.preventDefault();
                saveAndMove('wrong');
            } else if (key === 'a') {
                event.preventDefault();
                if (state.autoRunning) {
                    stopAutoRun();
                } else {
                    startAutoRun();
                }
            } else if (key === 's') {
                event.preventDefault();
                stopAutoRun(false);
                speakText(termForSpeech(cards[state.index]?.term));
            } else if (key === 'e') {
                event.preventDefault();
                stopAutoRun(false);
                if (state.settings.speakExample) {
                    speakText(cards[state.index]?.example_sentence);
                }
            } else if (event.key === 'Escape') {
                stopAutoRun();
            }
        });

        syncAutoRunButton();
        render(true);
    }

    function buildChoices(cards, correctDefinition, count) {
        const choices = uniqueValues([correctDefinition, ...shuffle(cards.map((card) => card.definition))]);
        return shuffle(choices.slice(0, Math.max(1, count)));
    }

    function setupLearn() {
        const app = $('#learnApp');
        if (!app) return;

        const cards = parseJsonScript('learnData');
        if (!cards.length) return;

        const setId = Number(app.dataset.setId || 0);
        const area = $('#learnQuestionArea');
        const feedback = $('#learnFeedback');
        const nextButton = $('#learnNext');
        const review = $('#learnReview');
        const progressText = $('#learnProgressText');
        const progressBar = $('#learnProgressBar');

        const settings = {
            showImmediate: boolSetting('learn_show_correct_immediately', true),
            showReview: boolSetting('learn_show_correct_in_review', true),
            retryWrong: boolSetting('learn_retry_wrong_answers', true),
            autoRetry: boolSetting('learn_auto_retry_wrong_answers', false),
            showIpa: boolSetting('learn_show_ipa', true)
        };

        const state = {
            questions: [],
            index: 0,
            roundCorrect: 0,
            roundWrong: 0,
            answered: false,
            started: false,
            retryMode: false,
            completedRetry: false,
            wrongMap: new Map()
        };

        const toggles = [
            ['learnShowCorrectImmediately', 'showImmediate', 'learn_show_correct_immediately'],
            ['learnShowCorrectInReview', 'showReview', 'learn_show_correct_in_review'],
            ['learnRetryWrongAnswers', 'retryWrong', 'learn_retry_wrong_answers'],
            ['learnAutoRetryWrongAnswers', 'autoRetry', 'learn_auto_retry_wrong_answers'],
            ['learnShowIpa', 'showIpa', 'learn_show_ipa']
        ];

        toggles.forEach(([id, prop, key]) => {
            const toggle = document.getElementById(id);
            if (!toggle) return;
            toggle.checked = settings[prop];
            toggle.addEventListener('change', () => {
                settings[prop] = toggle.checked;
                setBoolSetting(key, settings[prop]);
                if (prop === 'showIpa') {
                    syncLearnIpa();
                    return;
                }
                if (review && !review.hidden && state.started && state.index >= state.questions.length) {
                    renderReview();
                }
            });
        });

        function pronunciationMarkup(card, tagName) {
            if (!card?.pronunciation) return '';
            const hidden = settings.showIpa ? '' : ' hidden';
            return `<${tagName} class="pronunciation learn-ipa"${hidden}>${escapeHtml(card.pronunciation)}</${tagName}>`;
        }

        function syncLearnIpa() {
            $$('.learn-ipa', app).forEach((el) => {
                el.hidden = !settings.showIpa;
            });
        }

        function renderIntro() {
            if (!area) return;
            area.innerHTML = `
                <div class="question-card">
                    <span class="card-label">Learn</span>
                    <h3>Sẵn sàng bắt đầu bài học</h3>
                    <p class="text-muted mb-0">Bài học sẽ trộn câu hỏi chọn đáp án và nhập từ tiếng Anh.</p>
                </div>
            `;
            nextButton.disabled = false;
        }

        function makeQuestion(card, type) {
            const id = `${card.id}:${type}`;
            return {
                id,
                card,
                type,
                prompt: type === 'choice' ? `Chọn nghĩa tiếng Việt của "${card.term}"` : `Nhập từ tiếng Anh có nghĩa là "${card.definition}"`,
                correctAnswer: type === 'choice' ? card.definition : card.term,
                choices: type === 'choice' ? buildChoices(cards, card.definition, 4) : []
            };
        }

        function buildInitialQuestions() {
            return shuffle(cards.flatMap((card) => [makeQuestion(card, 'choice'), makeQuestion(card, 'written')]));
        }

        function questionFromWrong(wrong) {
            return {
                ...wrong.question,
                choices: wrong.question.type === 'choice' ? buildChoices(cards, wrong.question.card.definition, 4) : []
            };
        }

        function startRound(questions, retryMode) {
            state.questions = shuffle(questions);
            state.index = 0;
            state.roundCorrect = 0;
            state.roundWrong = 0;
            state.answered = false;
            state.started = true;
            state.retryMode = retryMode;
            feedback.hidden = true;
            feedback.textContent = '';
            review.hidden = true;
            nextButton.textContent = 'Tiếp tục';
            nextButton.disabled = true;
            renderQuestion();
        }

        function currentQuestion() {
            return state.questions[state.index];
        }

        function updateProgress() {
            const total = state.questions.length || 1;
            const current = Math.min(state.index + (state.answered ? 1 : 0), total);
            if (progressText) {
                progressText.textContent = `Câu ${Math.min(state.index + 1, total)}/${total} · Đúng ${state.roundCorrect} · Sai ${state.roundWrong}`;
            }
            if (progressBar) {
                progressBar.style.width = `${(current / total) * 100}%`;
            }
        }

        function renderQuestion() {
            const question = currentQuestion();
            updateProgress();
            feedback.hidden = true;
            feedback.className = 'feedback-box';
            nextButton.disabled = true;
            state.answered = false;

            if (!question) {
                renderReview();
                return;
            }

            const pronunciation = pronunciationMarkup(question.card, 'p');
            let controls = '';

            if (question.type === 'choice') {
                controls = `<div class="choice-grid">${question.choices.map((choice) => `
                    <button class="choice-option" type="button" data-answer="${escapeHtml(choice)}">${escapeHtml(choice)}</button>
                `).join('')}</div>`;
            } else {
                controls = `
                    <form id="learnWrittenForm" class="written-answer">
                        <input class="form-control form-control-lg" id="learnWrittenInput" type="text" autocomplete="off" placeholder="Nhập từ tiếng Anh">
                        <button class="btn btn-primary" type="submit">Kiểm tra</button>
                    </form>
                `;
            }

            area.innerHTML = `
                <div class="question-card">
                    <span class="card-label">${state.retryMode ? 'Retry' : 'Learn'}</span>
                    <h3>${escapeHtml(question.prompt)}</h3>
                    ${pronunciation}
                    ${controls}
                </div>
            `;

            $$('.choice-option', area).forEach((button) => {
                button.addEventListener('click', () => answerQuestion(button.dataset.answer || '', button));
            });

            $('#learnWrittenForm')?.addEventListener('submit', (event) => {
                event.preventDefault();
                answerQuestion($('#learnWrittenInput')?.value || '', null);
            });
        }

        function answerQuestion(answer, selectedButton) {
            if (state.answered) return;
            const question = currentQuestion();
            if (!question) return;

            const isCorrect = normalizeAnswer(answer) === normalizeAnswer(question.correctAnswer);
            state.answered = true;
            nextButton.disabled = false;
            $$('.choice-option', area).forEach((button) => {
                button.disabled = true;
                if (normalizeAnswer(button.dataset.answer) === normalizeAnswer(question.correctAnswer)) {
                    button.classList.add('is-correct-choice');
                }
            });

            if (isCorrect) {
                state.roundCorrect += 1;
                state.wrongMap.delete(question.id);
                feedback.className = 'feedback-box is-correct';
                feedback.innerHTML = '<strong>Đúng rồi.</strong>';
                if (selectedButton) selectedButton.classList.add('is-correct-choice');
                saveProgress(setId, question.card.id, 'learn', 'correct', answer);
            } else {
                state.roundWrong += 1;
                const wrongItem = {
                    id: question.id,
                    card: question.card,
                    question,
                    userAnswer: answer || '(chưa trả lời)',
                    correctAnswer: question.correctAnswer
                };
                state.wrongMap.set(question.id, wrongItem);
                feedback.className = 'feedback-box is-wrong';
                feedback.innerHTML = settings.showImmediate
                    ? `<strong>Sai rồi.</strong><span>Đáp án đúng: ${escapeHtml(question.correctAnswer)}</span>`
                    : '<strong>Sai rồi.</strong>';
                if (selectedButton) selectedButton.classList.add('is-wrong-choice');
                saveProgress(setId, question.card.id, 'learn', 'wrong', answer);
            }

            feedback.hidden = false;
            updateProgress();
        }

        function renderReview() {
            const total = state.questions.length;
            const wrongItems = Array.from(state.wrongMap.values());
            const percent = total ? Math.round((state.roundCorrect / total) * 100) : 0;

            progressText.textContent = `Hoàn thành · ${percent}% đúng`;
            progressBar.style.width = '100%';
            area.innerHTML = '';
            feedback.hidden = true;
            nextButton.disabled = false;
            nextButton.textContent = 'Học lại từ đầu';

            const wrongList = wrongItems.length
                ? wrongItems.map((item) => `
                    <div class="wrong-item">
                        <div>
                            <strong>${escapeHtml(item.card.term)}</strong>
                            ${pronunciationMarkup(item.card, 'span')}
                            ${settings.showReview ? `<p>Nghĩa đúng: ${escapeHtml(item.card.definition)}</p>` : ''}
                            <p>Câu trả lời của bạn: <span>${escapeHtml(item.userAnswer)}</span></p>
                            ${settings.showReview ? `<p>Đáp án đúng: <span>${escapeHtml(item.correctAnswer)}</span></p>` : ''}
                            ${item.card.example_sentence ? `<p class="example-text">${escapeHtml(item.card.example_sentence)}</p>` : ''}
                        </div>
                    </div>
                `).join('')
                : `<div class="success-review">${state.retryMode || state.completedRetry ? 'Bạn đã học lại xong tất cả câu sai!' : 'Tuyệt vời! Bạn đã trả lời đúng tất cả.'}</div>`;

            review.innerHTML = `
                <div class="result-summary">
                    <div><span>Tổng câu hỏi</span><strong>${total}</strong></div>
                    <div><span>Số câu đúng</span><strong>${state.roundCorrect}</strong></div>
                    <div><span>Số câu sai</span><strong>${state.roundWrong}</strong></div>
                    <div><span>Phần trăm đúng</span><strong>${percent}%</strong></div>
                </div>
                <div class="wrong-review-card">
                    <div class="panel-header px-0">
                        <div>
                            <h2>Câu cần học lại</h2>
                            <p>${wrongItems.length ? `${wrongItems.length} câu còn cần luyện lại.` : 'Không còn câu sai.'}</p>
                        </div>
                    </div>
                    ${wrongList}
                    ${settings.retryWrong && wrongItems.length ? '<button class="btn btn-warning mt-3" id="retryWrongLearn" type="button">Học lại câu sai</button>' : ''}
                </div>
            `;
            review.hidden = false;

            $('#retryWrongLearn')?.addEventListener('click', () => startRetryRound());

            if (settings.autoRetry && wrongItems.length) {
                setTimeout(() => {
                    if (settings.autoRetry && state.wrongMap.size) {
                        startRetryRound();
                    }
                }, 1800);
            }
        }

        function startRetryRound() {
            const wrongItems = Array.from(state.wrongMap.values());
            if (!wrongItems.length) {
                state.completedRetry = true;
                renderReview();
                return;
            }
            startRound(wrongItems.map(questionFromWrong), true);
        }

        nextButton?.addEventListener('click', () => {
            if (!state.started || state.index >= state.questions.length) {
                state.completedRetry = false;
                state.wrongMap.clear();
                startRound(buildInitialQuestions(), false);
                return;
            }

            if (!state.answered) return;
            state.index += 1;

            if (state.index >= state.questions.length) {
                if (state.retryMode && state.wrongMap.size === 0) {
                    state.completedRetry = true;
                }
                renderReview();
            } else {
                renderQuestion();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (!document.body.contains(app) || isTypingTarget(event.target)) {
                return;
            }

            if (/^[1-4]$/.test(event.key)) {
                const buttons = $$('.choice-option', area).filter((button) => !button.disabled);
                const button = buttons[Number(event.key) - 1];
                if (button) {
                    event.preventDefault();
                    button.click();
                }
                return;
            }

            if (event.key === 'Enter' || isSpaceKey(event)) {
                if (isInteractiveTarget(event.target)) return;
                if (nextButton && !nextButton.disabled) {
                    event.preventDefault();
                    nextButton.click();
                }
            }
        });

        renderIntro();
    }

    function setupTest() {
        const app = $('#testApp');
        if (!app) return;

        const cards = parseJsonScript('testData');
        if (!cards.length) return;

        const setId = Number(app.dataset.setId || 0);
        const questionsEl = $('#testQuestions');
        const resultEl = $('#testResult');
        const submitButton = $('#testSubmit');
        const restartButton = $('#testRestart');
        let questions = [];
        let submitted = false;

        function buildTestQuestions() {
            const types = ['choice', 'true_false', 'fill_blank', 'written'];
            return shuffle(cards).map((card, index) => {
                const type = types[index % types.length];
                if (type === 'choice') {
                    return {
                        type,
                        card,
                        prompt: `Chọn nghĩa đúng của "${card.term}"`,
                        choices: buildChoices(cards, card.definition, 4),
                        correctAnswer: card.definition
                    };
                }
                if (type === 'true_false') {
                    const useTrue = cards.length === 1 || Math.random() >= 0.5;
                    const otherDefinitions = cards.filter((item) => item.id !== card.id).map((item) => item.definition);
                    const shownDefinition = useTrue ? card.definition : shuffle(otherDefinitions)[0] || card.definition;
                    return {
                        type,
                        card,
                        prompt: `"${card.term}" có nghĩa là "${shownDefinition}"`,
                        correctAnswer: useTrue ? 'true' : 'false',
                        shownDefinition
                    };
                }
                if (type === 'fill_blank') {
                    const termPattern = new RegExp(card.term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
                    const sentence = card.example_sentence && termPattern.test(card.example_sentence)
                        ? card.example_sentence.replace(termPattern, '_____')
                        : `_____ = ${card.definition}`;
                    return {
                        type,
                        card,
                        prompt: `Điền từ còn thiếu: ${sentence}`,
                        correctAnswer: card.term
                    };
                }
                return {
                    type,
                    card,
                    prompt: `Viết từ tiếng Anh có nghĩa là "${card.definition}"`,
                    correctAnswer: card.term
                };
            });
        }

        function renderTest() {
            submitted = false;
            questions = buildTestQuestions();
            resultEl.hidden = true;
            resultEl.innerHTML = '';
            submitButton.disabled = false;
            questionsEl.innerHTML = questions.map((question, index) => {
                let answerControl = '';
                if (question.type === 'choice') {
                    answerControl = question.choices.map((choice, choiceIndex) => `
                        <label class="test-option">
                            <input type="radio" name="q${index}" value="${escapeHtml(choice)}">
                            <span>${escapeHtml(choice)}</span>
                        </label>
                    `).join('');
                } else if (question.type === 'true_false') {
                    answerControl = `
                        <label class="test-option"><input type="radio" name="q${index}" value="true"><span>True</span></label>
                        <label class="test-option"><input type="radio" name="q${index}" value="false"><span>False</span></label>
                    `;
                } else {
                    answerControl = `<input class="form-control" type="text" name="q${index}" autocomplete="off" placeholder="Nhập đáp án">`;
                }
                return `
                    <div class="test-question" data-index="${index}">
                        <span class="card-label">${escapeHtml(question.type.replace('_', ' '))}</span>
                        <h3>Câu ${index + 1}. ${escapeHtml(question.prompt)}</h3>
                        ${question.card.pronunciation ? `<p class="pronunciation">${escapeHtml(question.card.pronunciation)}</p>` : ''}
                        <div class="test-answer">${answerControl}</div>
                    </div>
                `;
            }).join('');
        }

        function getAnswer(index, type) {
            if (type === 'choice' || type === 'true_false') {
                return $(`input[name="q${index}"]:checked`, questionsEl)?.value || '';
            }
            return $(`input[name="q${index}"]`, questionsEl)?.value || '';
        }

        function submitTest() {
            if (submitted) return;
            submitted = true;
            let score = 0;
            const rows = questions.map((question, index) => {
                const answer = getAnswer(index, question.type);
                const isCorrect = normalizeAnswer(answer) === normalizeAnswer(question.correctAnswer);
                if (isCorrect) score += 1;
                return `
                    <div class="answer-row ${isCorrect ? 'is-correct' : 'is-wrong'}">
                        <div>
                            <strong>Câu ${index + 1}: ${escapeHtml(question.prompt)}</strong>
                            <p>Bạn trả lời: ${escapeHtml(answer || '(chưa trả lời)')}</p>
                            <p>Đáp án đúng: ${escapeHtml(question.correctAnswer)}</p>
                        </div>
                        <span>${isCorrect ? 'Đúng' : 'Sai'}</span>
                    </div>
                `;
            });

            const total = questions.length;
            const percent = total ? Math.round((score / total) * 100) : 0;
            resultEl.innerHTML = `
                <div class="result-summary">
                    <div><span>Đúng</span><strong>${score}</strong></div>
                    <div><span>Sai</span><strong>${total - score}</strong></div>
                    <div><span>Điểm</span><strong>${percent}%</strong></div>
                </div>
                <div class="answer-review">${rows.join('')}</div>
            `;
            resultEl.hidden = false;
            submitButton.disabled = true;
            $$('input', questionsEl).forEach((input) => {
                input.disabled = true;
            });
            saveTestResult(setId, score, total, 'test');
        }

        submitButton?.addEventListener('click', submitTest);
        restartButton?.addEventListener('click', renderTest);
        document.addEventListener('keydown', (event) => {
            if (!document.body.contains(app)) return;
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && submitButton && !submitButton.disabled) {
                event.preventDefault();
                submitButton.click();
            }
        });
        renderTest();
    }

    function setupBlocks() {
        const app = $('#blocksApp');
        if (!app) return;
        const cards = shuffle(parseJsonScript('blocksData')).slice(0, 8);
        if (!cards.length) return;

        const setId = Number(app.dataset.setId || 0);
        const grid = $('#blocksGrid');
        const timerEl = $('#blocksTimer');
        const scoreEl = $('#blocksScore');
        const resultEl = $('#blocksResult');
        const restartButton = $('#blocksRestart');
        let selectedTerm = null;
        let selectedDefinition = null;
        let score = 0;
        let matched = 0;
        let seconds = 0;
        let timerId = null;
        let locked = false;

        function startTimer() {
            clearInterval(timerId);
            seconds = 0;
            timerEl.textContent = '0s';
            timerId = setInterval(() => {
                seconds += 1;
                timerEl.textContent = `${seconds}s`;
            }, 1000);
        }

        function renderBlocks() {
            selectedTerm = null;
            selectedDefinition = null;
            score = 0;
            matched = 0;
            locked = false;
            scoreEl.textContent = '0';
            resultEl.hidden = true;
            const items = shuffle(cards.flatMap((card) => [
                { type: 'term', value: card.term, id: card.id },
                { type: 'definition', value: card.definition, id: card.id }
            ]));
            grid.innerHTML = items.map((item) => `
                <button class="block-tile" type="button" data-type="${item.type}" data-id="${item.id}">
                    ${escapeHtml(item.value)}
                </button>
            `).join('');
            $$('.block-tile', grid).forEach((button) => button.addEventListener('click', handleClick));
            startTimer();
        }

        function resetSelection() {
            selectedTerm = null;
            selectedDefinition = null;
            $$('.block-tile.is-selected', grid).forEach((button) => button.classList.remove('is-selected'));
        }

        function handleClick(event) {
            if (locked) return;
            const button = event.currentTarget;
            if (button.disabled || button.classList.contains('is-matched')) return;

            const type = button.dataset.type;
            if (type === 'term') {
                if (selectedTerm) selectedTerm.classList.remove('is-selected');
                selectedTerm = button;
            } else {
                if (selectedDefinition) selectedDefinition.classList.remove('is-selected');
                selectedDefinition = button;
            }
            button.classList.add('is-selected');

            if (!selectedTerm || !selectedDefinition) return;
            locked = true;

            if (selectedTerm.dataset.id === selectedDefinition.dataset.id) {
                selectedTerm.classList.add('is-correct');
                selectedDefinition.classList.add('is-correct');
                setTimeout(() => {
                    [selectedTerm, selectedDefinition].forEach((tile) => {
                        tile.classList.add('is-matched');
                        tile.disabled = true;
                    });
                    const cardId = Number(selectedTerm.dataset.id);
                    score += 10;
                    matched += 1;
                    scoreEl.textContent = String(score);
                    saveProgress(setId, cardId, 'blocks', 'correct', 'matched');
                    resetSelection();
                    locked = false;
                    if (matched === cards.length) {
                        clearInterval(timerId);
                        resultEl.innerHTML = `<strong>Hoàn thành!</strong> Điểm: ${score}. Thời gian: ${seconds}s.`;
                        resultEl.hidden = false;
                    }
                }, 350);
            } else {
                selectedTerm.classList.add('is-wrong');
                selectedDefinition.classList.add('is-wrong');
                saveProgress(setId, Number(selectedTerm.dataset.id), 'blocks', 'wrong', selectedDefinition.textContent.trim());
                setTimeout(() => {
                    $$('.block-tile.is-wrong', grid).forEach((tile) => tile.classList.remove('is-wrong'));
                    resetSelection();
                    locked = false;
                }, 650);
            }
        }

        restartButton?.addEventListener('click', renderBlocks);
        document.addEventListener('keydown', (event) => {
            if (!document.body.contains(app) || isTypingTarget(event.target)) return;
            if (event.key.toLowerCase() === 'r') {
                event.preventDefault();
                renderBlocks();
            }
        });
        renderBlocks();
    }

    function setupBlast() {
        const app = $('#blastApp');
        if (!app) return;
        const cards = parseJsonScript('blastData');
        if (!cards.length) return;

        const setId = Number(app.dataset.setId || 0);
        const timerEl = $('#blastTimer');
        const scoreEl = $('#blastScore');
        const answeredEl = $('#blastAnswered');
        const wordEl = $('#blastWord');
        const optionsEl = $('#blastOptions');
        const feedbackEl = $('#blastFeedback');
        const resultEl = $('#blastResult');
        const restartButton = $('#blastRestart');
        let score = 0;
        let answered = 0;
        let timeLeft = 60;
        let currentCard = null;
        let timerId = null;
        let finished = false;

        function startGame() {
            score = 0;
            answered = 0;
            timeLeft = 60;
            finished = false;
            scoreEl.textContent = '0';
            answeredEl.textContent = '0';
            timerEl.textContent = '60s';
            resultEl.hidden = true;
            feedbackEl.hidden = true;
            clearInterval(timerId);
            timerId = setInterval(() => {
                timeLeft -= 1;
                timerEl.textContent = `${timeLeft}s`;
                if (timeLeft <= 0) finishGame();
            }, 1000);
            nextQuestion();
        }

        function finishGame() {
            finished = true;
            clearInterval(timerId);
            $$('.blast-option', optionsEl).forEach((button) => {
                button.disabled = true;
            });
            resultEl.innerHTML = `<strong>Hết giờ!</strong> Điểm cuối cùng: ${score}. Số câu đã trả lời: ${answered}.`;
            resultEl.hidden = false;
        }

        function nextQuestion() {
            if (finished) return;
            currentCard = shuffle(cards)[0];
            wordEl.textContent = currentCard.term;
            const choices = buildChoices(cards, currentCard.definition, 4);
            optionsEl.innerHTML = choices.map((choice) => `
                <button class="blast-option" type="button" data-answer="${escapeHtml(choice)}">${escapeHtml(choice)}</button>
            `).join('');
            $$('.blast-option', optionsEl).forEach((button) => {
                button.addEventListener('click', () => answer(button.dataset.answer || '', button));
            });
        }

        function answer(answerText, button) {
            if (finished || !currentCard) return;
            const correct = normalizeAnswer(answerText) === normalizeAnswer(currentCard.definition);
            answered += 1;
            if (correct) {
                score += 10;
                button.classList.add('is-correct');
                feedbackEl.className = 'feedback-box is-correct mt-3';
                feedbackEl.textContent = 'Đúng. +10 điểm';
                saveProgress(setId, currentCard.id, 'blast', 'correct', answerText);
            } else {
                score = Math.max(0, score - 2);
                button.classList.add('is-wrong');
                feedbackEl.className = 'feedback-box is-wrong mt-3';
                feedbackEl.textContent = `Sai. Đáp án đúng: ${currentCard.definition}`;
                saveProgress(setId, currentCard.id, 'blast', 'wrong', answerText);
            }
            scoreEl.textContent = String(score);
            answeredEl.textContent = String(answered);
            feedbackEl.hidden = false;
            $$('.blast-option', optionsEl).forEach((option) => {
                option.disabled = true;
            });
            setTimeout(nextQuestion, 550);
        }

        restartButton?.addEventListener('click', startGame);
        document.addEventListener('keydown', (event) => {
            if (!document.body.contains(app) || isTypingTarget(event.target)) return;
            if (/^[1-4]$/.test(event.key)) {
                const buttons = $$('.blast-option', optionsEl).filter((button) => !button.disabled);
                const button = buttons[Number(event.key) - 1];
                if (button) {
                    event.preventDefault();
                    button.click();
                }
            } else if (event.key.toLowerCase() === 'r') {
                event.preventDefault();
                startGame();
            }
        });
        startGame();
    }

    function setupMatch() {
        const app = $('#matchApp');
        if (!app) return;
        const cards = shuffle(parseJsonScript('matchData')).slice(0, 10);
        if (!cards.length) return;

        const setId = Number(app.dataset.setId || 0);
        const termColumn = $('#matchTerms');
        const definitionColumn = $('#matchDefinitions');
        const timerEl = $('#matchTimer');
        const scoreEl = $('#matchScore');
        const resultEl = $('#matchResult');
        const restartButton = $('#matchRestart');
        let selectedTerm = null;
        let selectedDefinition = null;
        let matched = 0;
        let score = 0;
        let seconds = 0;
        let timerId = null;
        let locked = false;

        function startTimer() {
            clearInterval(timerId);
            seconds = 0;
            timerEl.textContent = '0s';
            timerId = setInterval(() => {
                seconds += 1;
                timerEl.textContent = `${seconds}s`;
            }, 1000);
        }

        function renderMatch() {
            selectedTerm = null;
            selectedDefinition = null;
            matched = 0;
            score = 0;
            locked = false;
            scoreEl.textContent = '0';
            resultEl.hidden = true;
            termColumn.innerHTML = shuffle(cards).map((card) => `
                <button class="match-card" type="button" data-type="term" data-id="${card.id}">${escapeHtml(card.term)}</button>
            `).join('');
            definitionColumn.innerHTML = shuffle(cards).map((card) => `
                <button class="match-card" type="button" data-type="definition" data-id="${card.id}">${escapeHtml(card.definition)}</button>
            `).join('');
            $$('.match-card', app).forEach((button) => button.addEventListener('click', handleClick));
            startTimer();
        }

        function clearSelection() {
            selectedTerm = null;
            selectedDefinition = null;
            $$('.match-card.is-selected', app).forEach((card) => card.classList.remove('is-selected'));
        }

        function handleClick(event) {
            if (locked) return;
            const button = event.currentTarget;
            if (button.disabled || button.classList.contains('is-matched')) return;
            const type = button.dataset.type;

            if (type === 'term') {
                if (selectedTerm) selectedTerm.classList.remove('is-selected');
                selectedTerm = button;
            } else {
                if (selectedDefinition) selectedDefinition.classList.remove('is-selected');
                selectedDefinition = button;
            }
            button.classList.add('is-selected');

            if (!selectedTerm || !selectedDefinition) return;
            locked = true;

            if (selectedTerm.dataset.id === selectedDefinition.dataset.id) {
                [selectedTerm, selectedDefinition].forEach((card) => {
                    card.classList.add('is-matched');
                    card.disabled = true;
                });
                matched += 1;
                score += 10;
                scoreEl.textContent = String(score);
                saveProgress(setId, Number(selectedTerm.dataset.id), 'match', 'correct', 'matched');
                clearSelection();
                locked = false;
                if (matched === cards.length) {
                    clearInterval(timerId);
                    resultEl.innerHTML = `<strong>Chiến thắng!</strong> Bạn hoàn thành ${cards.length} cặp trong ${seconds}s với ${score} điểm.`;
                    resultEl.hidden = false;
                }
            } else {
                selectedTerm.classList.add('is-wrong');
                selectedDefinition.classList.add('is-wrong');
                saveProgress(setId, Number(selectedTerm.dataset.id), 'match', 'wrong', selectedDefinition.textContent.trim());
                setTimeout(() => {
                    $$('.match-card.is-wrong', app).forEach((card) => card.classList.remove('is-wrong'));
                    clearSelection();
                    locked = false;
                }, 650);
            }
        }

        restartButton?.addEventListener('click', renderMatch);
        document.addEventListener('keydown', (event) => {
            if (!document.body.contains(app) || isTypingTarget(event.target)) return;
            if (event.key.toLowerCase() === 'r') {
                event.preventDefault();
                renderMatch();
            }
        });
        renderMatch();
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (!window.location.hash) {
            window.scrollTo(0, 0);
        }
        setupSidebar();
        setupConfirmDelete();
        setupAlerts();
        setupDynamicCardRows();
        setupVisibilityControls();
        setupFlashcards();
        setupLearn();
        setupTest();
        setupBlocks();
        setupBlast();
        setupMatch();
    });
})();
