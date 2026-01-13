 const display = document.getElementById('display');
let currentInput = '0';
let resetDisplay = false;

// Update the display
function updateDisplay() {
    display.textContent = currentInput;
}

// Append to current input
function appendToDisplay(value) {
    if (currentInput === '0' || resetDisplay) {
        // If the last character is a digit and we're appending an operator, append to current input
        if (/[0-9]/.test(currentInput.slice(-1)) && /[+\-*/]/.test(value)) {
            currentInput += value;
        } else {
            currentInput = value;
        }
        resetDisplay = false;
    } else {
        // If current input is a result and user enters a number, start new input
        if (!isNaN(value) && !isNaN(currentInput)) {
            currentInput = value;
        } else {
            currentInput += value;
        }
    }
    updateDisplay();
}

// Clear the display
function clearDisplay() {
    currentInput = '0';
    updateDisplay();
}

// Calculate the result
function calculate() {
    try {
        // Replace × with * for evaluation
        const expression = currentInput.replace(/×/g, '*');
        const result = eval(expression);
        currentInput = result.toString();
        updateDisplay();
        // Don't reset display, allow chaining operations
        resetDisplay = false;
    } catch (error) {
        currentInput = 'Error';
        updateDisplay();
        setTimeout(clearDisplay, 1000);
    }
}

// Keyboard support - only when calculator is focused/hovered
const calculator = document.querySelector('.calculator');
if (calculator) {
    let calculatorFocused = false;

    calculator.addEventListener('mouseenter', () => calculatorFocused = true);
    calculator.addEventListener('mouseleave', () => calculatorFocused = false);

    document.addEventListener('keydown', (e) => {
        // Only handle keys if calculator is focused/hovered or if an input inside it is focused
        const activeElement = document.activeElement;
        const isInputField = activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'SELECT');

        // Skip if user is typing in an input field (but not the calculator display)
        if (isInputField && !activeElement.closest('.calculator')) {
            return;
        }

        // Only proceed if calculator is hovered or display is active
        if (!calculatorFocused && !activeElement.closest('.calculator')) {
            return;
        }

        const key = e.key;

        if (/[0-9.]/.test(key)) {
            e.preventDefault();
            appendToDisplay(key);
        } else if (['+', '-', '*', '/', '(', ')'].includes(key)) {
            e.preventDefault();
            appendToDisplay(key);
        } else if (key === 'Enter' || key === '=') {
            e.preventDefault();
            calculate();
        } else if (key === 'Escape') {
            e.preventDefault();
            clearDisplay();
        } else if (key === 'Backspace') {
            e.preventDefault();
            currentInput = currentInput.slice(0, -1) || '0';
            updateDisplay();
        }
    });
}