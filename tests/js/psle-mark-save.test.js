import fs from 'fs';

const source = fs.readFileSync('resources/views/mark-entry/psle/partials/entry-sheet.blade.php', 'utf8');

describe('PSLE mark save frontend resilience', () => {
  test('uses debounce and one in-flight save state per candidate field', () => {
    expect(source).toContain('const PSLE_SAVE_DEBOUNCE_MS = 150');
    expect(source).toContain('state.inFlight && attempt === 0');
    expect(source).toContain('pendingValue');
  });

  test('never shows a queued state to officers', () => {
    expect(source).not.toContain('Queued');
  });

  test('does not surface intentional AbortError as a modal validation failure', () => {
    expect(source).toContain("if (err.name === 'AbortError')");
    expect(source).toContain("console.debug('PSLE mark save aborted intentionally.'");
    expect(source).toContain('return;');
    expect(source).not.toContain("err.message !== 'signal is aborted without reason'");
  });

  test('keeps retry delay in-flight and only marks failed after retries are exhausted', () => {
    expect(source).toContain('retryScheduled = true');
    expect(source).toContain('if (retryScheduled)');
    expect(source).toContain("input.classList.remove('mark-input-save-error')");
    expect(source).toContain("input.classList.add('mark-input-save-error')");
  });

  test('saves on row navigation and shows one completion notification', () => {
    expect(source).toContain('function checkCompletion');
    expect(source).toContain('psleCompletionNotified');
    expect(source).toContain('All marks for this score sheet have been entered successfully.');
    expect(source).toContain("event.key === 'Enter'");
    expect(source).toContain("event.key === 'Tab'");
    expect(source).toContain('saveRow(currentInput)');
    expect(source).toContain('moveToNextInput(currentInput)');
  });

  test('successful row save uses Entered badge text', () => {
    expect(source).toContain("setMarkStatus(input, 'Entered', 'badge-green')");
    expect(source).not.toContain("setMarkStatus(input, 'Saved'");
  });
});
