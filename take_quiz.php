<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz — NikkQuiz</title>
    <meta name="description" content="Enter your PIN and take your quiz.">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0f0f1a; }
        .glass-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .gradient-text {
            background: linear-gradient(135deg, #a78bfa, #6d28d9, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(109, 40, 217, 0.35);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(109, 40, 217, 0.5); }
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .input-field:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
            outline: none;
            background: rgba(255, 255, 255, 0.08);
        }
        .fade-in { animation: fadeIn 0.5s ease forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-in { animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .option-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .option-btn:hover {
            background: rgba(124, 58, 237, 0.08);
            border-color: rgba(124, 58, 237, 0.25);
        }
        .option-btn.selected {
            background: rgba(124, 58, 237, 0.15);
            border-color: rgba(124, 58, 237, 0.5);
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.1);
        }
        .option-btn.selected .option-circle {
            background: #7c3aed;
            border-color: #7c3aed;
        }
        .option-circle {
            width: 20px; height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.25s ease;
            flex-shrink: 0;
        }
        .timer-ring {
            transition: stroke-dashoffset 1s linear;
        }
        .timer-warning { color: #fbbf24; }
        .timer-danger { color: #ef4444; animation: pulseTimer 0.5s ease-in-out infinite; }
        @keyframes pulseTimer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .progress-bar {
            background: rgba(255, 255, 255, 0.06);
            overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(90deg, #7c3aed, #ec4899);
            transition: width 0.5s ease;
        }
        .pin-input {
            width: 48px; height: 56px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            color: #fff;
            transition: all 0.3s;
        }
        .pin-input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
            outline: none;
            background: rgba(255, 255, 255, 0.08);
        }
        .result-card {
            background: linear-gradient(135deg, rgba(124,58,237,0.1), rgba(236,72,153,0.05));
            border: 1px solid rgba(124,58,237,0.15);
        }
        .confetti { position: fixed; pointer-events: none; z-index: 100; }
        .nav-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.08);
            transition: all 0.25s;
        }
        .nav-dot.answered { background: rgba(124,58,237,0.5); border-color: rgba(124,58,237,0.6); }
        .nav-dot.current { background: #7c3aed; border-color: #7c3aed; transform: scale(1.3); }
    </style>
</head>
<body class="min-h-screen text-white">

    <!-- Decorative bg -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(124,58,237,0.12), transparent 70%);"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(236,72,153,0.08), transparent 70%);"></div>
    </div>

    <!-- ═══════════════════════════════════════════ PIN ENTRY SCREEN ═══ -->
    <div id="screenPin" class="relative z-10 min-h-screen flex items-center justify-center p-4">
        <div class="glass-card rounded-3xl w-full max-w-md p-8 text-center fade-in">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6" style="background: linear-gradient(135deg, rgba(124,58,237,0.2), rgba(236,72,153,0.15));">
                <svg class="w-8 h-8 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <p class="text-xs text-violet-400 uppercase tracking-wider mb-2" id="pinQuizLabel">Quiz</p>
            <h1 class="text-2xl font-bold gradient-text mb-2" id="pinQuizTitle">Enter your PIN</h1>
            <p class="text-gray-400 text-sm mb-8">Use the PIN from your class batch. The timer starts after your PIN is verified.</p>

            <div class="flex justify-center gap-2 mb-6" id="pinContainer">
                <input type="text" maxlength="1" class="pin-input" data-index="0" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                <input type="text" maxlength="1" class="pin-input" data-index="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                <input type="text" maxlength="1" class="pin-input" data-index="2" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                <input type="text" maxlength="1" class="pin-input" data-index="3" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                <input type="text" maxlength="1" class="pin-input" data-index="4" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                <input type="text" maxlength="1" class="pin-input" data-index="5" inputmode="numeric" pattern="[0-9]" autocomplete="off">
            </div>

            <div id="pinError" class="text-red-400 text-xs mb-4 hidden"></div>

            <button id="btnVerifyPin" class="btn-primary w-full text-white py-3 rounded-xl font-semibold text-sm" disabled>
                Verify & Start Quiz
            </button>
            <p class="mt-6 text-center"><a href="my_stats.php" class="text-sm text-violet-400/90 hover:text-violet-300">View my stats across all quizzes</a></p>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════ QUIZ SCREEN ════════ -->
    <div id="screenQuiz" class="relative z-10 hidden min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6">

            <!-- Top Bar -->
            <div class="flex items-center justify-between mb-6 fade-in">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Quiz</p>
                    <h2 class="text-lg font-bold text-white" id="quizNameDisplay"></h2>
                    <p class="text-xs text-gray-400" id="participantNameDisplay"></p>
                </div>
                <div class="text-right">
                    <div id="timerDisplay" class="text-3xl font-extrabold tabular-nums text-violet-300">--:--</div>
                    <p class="text-xs text-gray-500">remaining</p>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress-bar rounded-full h-1.5 mb-6">
                <div class="progress-fill h-full rounded-full" id="progressBar" style="width: 0%"></div>
            </div>

            <!-- Question Navigation Dots -->
            <div class="flex flex-wrap gap-1.5 justify-center mb-6" id="navDots"></div>

            <!-- Question Container -->
            <div id="questionContainer" class="glass-card rounded-2xl p-6 sm:p-8 mb-6 slide-in">
                <!-- Dynamic content -->
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center">
                <button id="btnPrev" class="text-gray-400 hover:text-white text-sm font-medium flex items-center gap-1 transition-colors disabled:opacity-30" disabled>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Previous
                </button>
                <span class="text-xs text-gray-500" id="questionCounter">1 / 10</span>
                <button id="btnNext" class="text-violet-400 hover:text-violet-300 text-sm font-medium flex items-center gap-1 transition-colors">
                    Next
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button id="btnSubmit" class="btn-primary text-white px-6 py-2 rounded-xl font-semibold text-sm hidden">
                    Submit Quiz
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════ RESULTS SCREEN ═════ -->
    <div id="screenResults" class="relative z-10 hidden min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-lg text-center fade-in">
            <div class="result-card rounded-3xl p-8 sm:p-10">
                <div class="text-6xl mb-4" id="resultEmoji">🤩</div>
                <h2 class="text-3xl font-extrabold text-white mb-1" id="resultGrade">Excellent</h2>
                <p class="text-gray-400 mb-6" id="resultQuizName"></p>

                <div class="glass-card rounded-2xl p-6 mb-6">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-2xl font-bold text-violet-300" id="resultScore">0</p>
                            <p class="text-xs text-gray-500">Correct</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-white" id="resultTotal">0</p>
                            <p class="text-xs text-gray-500">Total</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-emerald-300" id="resultPercent">0%</p>
                            <p class="text-xs text-gray-500">Score</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 text-sm text-left">
                    <div class="flex justify-between text-gray-400">
                        <span>Name</span>
                        <span class="text-white font-medium" id="resultName"></span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Started</span>
                        <span class="text-white font-medium" id="resultStartTime"></span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Finished</span>
                        <span class="text-white font-medium" id="resultEndTime"></span>
                    </div>
                </div>
            </div>

            <p class="text-gray-600 text-xs mt-6">Powered by NikkQuiz</p>
            <p class="mt-6">
                <a href="my_stats.php" class="inline-flex items-center gap-2 text-violet-400 hover:text-violet-300 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    View all my quiz stats
                </a>
            </p>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════ ERROR SCREEN ═══════ -->
    <div id="screenError" class="relative z-10 hidden min-h-screen items-center justify-center p-4">
        <div class="text-center fade-in max-w-md mx-auto px-4">
            <div class="text-6xl mb-4">🚫</div>
            <h2 class="text-2xl font-bold text-white mb-2" id="errorTitle">Unavailable</h2>
            <p class="text-gray-400" id="errorMessage">This quiz link is invalid or not active.</p>
        </div>
    </div>

    <script>
    const API = 'api/handler.php';
    const params = new URLSearchParams(window.location.search);
    const QUIZ_SLUG = params.get('q');

    let quizId = null;
    let questions = [];
    let answers = {};
    let currentQ = 0;
    let timerInterval = null;
    let remainingSeconds = 0;

    // ─── Initialization ──────────────────────────────────────────────
    $(document).ready(function() {
        if (!QUIZ_SLUG) {
            $('#errorTitle').text('Invalid link');
            $('#errorMessage').text('Missing quiz link. Ask your teacher for the correct URL.');
            showScreen('error');
            return;
        }
        $.post(API, { action: 'get_quiz_public', quiz_slug: QUIZ_SLUG }, function(res) {
            if (res.success && res.quiz) {
                if (res.quiz.status !== 'active') {
                    $('#errorTitle').text('Quiz not active');
                    $('#errorMessage').text('This quiz is not accepting attempts right now.');
                    showScreen('error');
                    return;
                }
                $('#pinQuizTitle').text(res.quiz.name);
                document.title = res.quiz.name + ' — NikkQuiz';
            } else {
                $('#errorTitle').text('Invalid link');
                $('#errorMessage').text('This quiz could not be found.');
                showScreen('error');
                return;
            }
            showScreen('pin');
            setupPinInputs();
        }, 'json').fail(function() {
            $('#errorTitle').text('Error');
            $('#errorMessage').text('Could not load quiz.');
            showScreen('error');
        });
    });

    function showScreen(name) {
        $('#screenPin, #screenQuiz, #screenResults, #screenError').addClass('hidden').removeClass('flex');
        const el = $(`#screen${name.charAt(0).toUpperCase() + name.slice(1)}`);
        el.removeClass('hidden');
        if (name === 'results' || name === 'error' || name === 'pin') {
            el.addClass('flex');
        }
    }

    // ─── PIN Input Logic ─────────────────────────────────────────────
    function setupPinInputs() {
        const inputs = $('.pin-input');

        inputs.on('input', function() {
            const val = $(this).val().replace(/\D/g, '');
            $(this).val(val);
            if (val && $(this).data('index') < 5) {
                inputs.eq($(this).data('index') + 1).focus();
            }
            checkPinComplete();
        });

        inputs.on('keydown', function(e) {
            if (e.key === 'Backspace' && !$(this).val() && $(this).data('index') > 0) {
                inputs.eq($(this).data('index') - 1).focus();
            }
        });

        inputs.on('paste', function(e) {
            e.preventDefault();
            const paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            for (let i = 0; i < paste.length; i++) {
                inputs.eq(i).val(paste[i]);
            }
            if (paste.length > 0) inputs.eq(Math.min(paste.length, 5)).focus();
            checkPinComplete();
        });

        inputs.first().focus();
    }

    function checkPinComplete() {
        const pin = getPin();
        $('#btnVerifyPin').prop('disabled', pin.length !== 6);
    }

    function getPin() {
        let pin = '';
        $('.pin-input').each(function() { pin += $(this).val(); });
        return pin;
    }

    // ─── Verify PIN ──────────────────────────────────────────────────
    $('#btnVerifyPin').click(function() {
        const pin = getPin();
        const btn = $(this);
        btn.prop('disabled', true).text('Verifying...');
        $('#pinError').addClass('hidden');

        $.post(API, { action: 'verify_pin', quiz_slug: QUIZ_SLUG, pin: pin }, function(res) {
            if (res.success) {
                quizId = res.quiz_id;
                startQuiz();
            } else {
                if (res.finished) {
                    window.location.href = 'my_stats.php';
                    return;
                }
                btn.prop('disabled', false).text('Verify \u0026 Start Quiz');
                $('#pinError').removeClass('hidden').text(res.error);
                $('.pin-input').val('').first().focus();
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).text('Verify \u0026 Start Quiz');
            $('#pinError').removeClass('hidden').text('Server error. Please try again.');
        });
    });

    // ─── Start Quiz ──────────────────────────────────────────────────
    function startQuiz() {
        $.post(API, { action: 'start_quiz' }, function(res) {
            if (res.success) {
                questions = res.data.questions;
                remainingSeconds = res.data.remaining_seconds;
                $('#quizNameDisplay').text(res.data.quiz_name);
                $('#participantNameDisplay').text(res.data.participant_name);
                document.title = res.data.quiz_name + ' — NikkQuiz';

                if (questions.length === 0) {
                    alert('No questions available.');
                    return;
                }

                buildNavDots();
                showScreen('quiz');
                renderQuestion(0);
                startTimer();
            } else {
                alert(res.error);
            }
        }, 'json');
    }

    // ─── Navigation Dots ─────────────────────────────────────────────
    function buildNavDots() {
        let html = '';
        questions.forEach((_, i) => {
            html += `<button class="nav-dot" data-q="${i}" title="Question ${i+1}"></button>`;
        });
        $('#navDots').html(html);

        $('.nav-dot').click(function() {
            renderQuestion($(this).data('q'));
        });
    }

    function updateNavDots() {
        $('.nav-dot').each(function() {
            const idx = $(this).data('q');
            $(this).toggleClass('answered', answers[idx] !== undefined);
            $(this).toggleClass('current', idx === currentQ);
        });
    }

    // ─── Render Question ─────────────────────────────────────────────
    function renderQuestion(idx) {
        currentQ = idx;
        const q = questions[idx];

        let optsHtml = '';
        const opts = Array.isArray(q.options) ? q.options : Object.values(q.options || {});
        opts.forEach((opt, oi) => {
            const selected = answers[idx] === oi ? 'selected' : '';
            optsHtml += `
            <button class="option-btn ${selected} w-full flex items-center gap-4 px-5 py-4 rounded-xl text-left" onclick="selectOption(${idx}, ${oi})">
                <span class="option-circle flex items-center justify-center">
                    ${answers[idx] === oi ? '<svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' : ''}
                </span>
                <span class="text-sm text-gray-200">${escapeHtml(opt)}</span>
            </button>`;
        });

        $('#questionContainer').html(`
            <p class="text-xs text-violet-400 font-semibold uppercase tracking-wider mb-3">Question ${idx + 1} of ${questions.length}</p>
            <h3 class="text-lg sm:text-xl font-semibold text-white leading-relaxed mb-6">${escapeHtml(q.question)}</h3>
            <div class="space-y-3">${optsHtml}</div>
        `);

        // Progress
        const progress = ((idx + 1) / questions.length) * 100;
        $('#progressBar').css('width', progress + '%');
        $('#questionCounter').text(`${idx + 1} / ${questions.length}`);

        // Nav buttons
        $('#btnPrev').prop('disabled', idx === 0);
        if (idx === questions.length - 1) {
            $('#btnNext').addClass('hidden');
            $('#btnSubmit').removeClass('hidden');
        } else {
            $('#btnNext').removeClass('hidden');
            $('#btnSubmit').addClass('hidden');
        }

        updateNavDots();
    }

    function selectOption(qIdx, optIdx) {
        answers[qIdx] = optIdx;
        renderQuestion(qIdx);
    }

    // ─── Navigation ──────────────────────────────────────────────────
    $('#btnPrev').click(() => { if (currentQ > 0) renderQuestion(currentQ - 1); });
    $('#btnNext').click(() => { if (currentQ < questions.length - 1) renderQuestion(currentQ + 1); });
    $('#btnSubmit').click(function() {
        const unanswered = questions.length - Object.keys(answers).length;
        let msg = 'Are you sure you want to submit your quiz?';
        if (unanswered > 0) {
            msg = `You have ${unanswered} unanswered question(s). Submit anyway?`;
        }
        if (confirm(msg)) {
            submitQuiz();
        }
    });

    // ─── Timer ───────────────────────────────────────────────────────
    function startTimer() {
        updateTimerDisplay();
        timerInterval = setInterval(function() {
            remainingSeconds--;
            updateTimerDisplay();

            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                alert('⏰ Time is up! Your quiz is being submitted.');
                submitQuiz();
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const min = Math.floor(Math.max(0, remainingSeconds) / 60);
        const sec = Math.max(0, remainingSeconds) % 60;
        const display = `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
        const el = $('#timerDisplay');
        el.text(display);

        el.removeClass('timer-warning timer-danger text-violet-300');
        if (remainingSeconds <= 30) {
            el.addClass('timer-danger');
        } else if (remainingSeconds <= 120) {
            el.addClass('timer-warning');
        } else {
            el.addClass('text-violet-300');
        }
    }

    // ─── Submit Quiz ─────────────────────────────────────────────────
    function submitQuiz() {
        clearInterval(timerInterval);
        $('#btnSubmit').prop('disabled', true).text('Submitting...');

        const answerArray = [];
        for (const [qIdx, selected] of Object.entries(answers)) {
            answerArray.push({
                question_index: questions[qIdx].index,
                selected: selected
            });
        }

        $.post(API, {
            action: 'submit_quiz',
            answers: JSON.stringify(answerArray)
        }, function(res) {
            if (res.success) {
                showResults(res.results);
            } else {
                alert(res.error);
            }
        }, 'json').fail(function() {
            alert('Failed to submit. Please check your connection.');
            $('#btnSubmit').prop('disabled', false).text('Submit Quiz');
        });
    }

    // ─── Show Results ────────────────────────────────────────────────
    function showResults(r) {
        showScreen('results');
        $('#resultEmoji').text(r.emoji);
        $('#resultGrade').text(r.grade);
        $('#resultQuizName').text(r.quiz_name);
        $('#resultScore').text(r.marks);
        $('#resultTotal').text(r.total);
        $('#resultPercent').text(r.percentage + '%');
        $('#resultName').text(r.name + ' (' + r.id + ')');
        $('#resultStartTime').text(r.start_time || '—');
        $('#resultEndTime').text(r.end_time || '—');

        if (r.percentage >= 60) {
            createConfetti();
        }
    }

    // ─── Confetti Effect ─────────────────────────────────────────────
    function createConfetti() {
        const colors = ['#7c3aed', '#ec4899', '#fbbf24', '#34d399', '#60a5fa'];
        for (let i = 0; i < 50; i++) {
            setTimeout(() => {
                const el = document.createElement('div');
                el.className = 'confetti';
                el.style.cssText = `
                    left: ${Math.random() * 100}vw;
                    top: -10px;
                    width: ${Math.random() * 8 + 4}px;
                    height: ${Math.random() * 8 + 4}px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
                    animation: confettiFall ${Math.random() * 2 + 2}s linear forwards;
                `;
                document.body.appendChild(el);
                setTimeout(() => el.remove(), 4000);
            }, i * 50);
        }
    }

    // Add confetti animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes confettiFall {
            to {
                transform: translateY(100vh) rotate(${Math.random() * 720}deg);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    function escapeHtml(str) {
        return $('<div>').text(str).html();
    }
    </script>
</body>
</html>
