import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { logger } from '@/utils/logger.js';

vi.mock('@/utils/logger', () => ({
  logger: {
    error: vi.fn(),
  },
}));

vi.mock('@/utils/markdownRenderer', () => ({
  renderMarkdown: vi.fn((text) => `<p>${text}</p>\n`),
}));

import { useStatementPopover } from '@/composables/useStatementPopover';
import { renderMarkdown } from '@/utils/markdownRenderer';

describe('useStatementPopover', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('does not show the popover before the delay elapses', () => {
    const { showPopover, onTriggerEnter } = useStatementPopover(() => 'Hello');
    onTriggerEnter();
    expect(showPopover.value).toBe(false);
    vi.advanceTimersByTime(499);
    expect(showPopover.value).toBe(false);
  });

  it('shows the popover with rendered markdown after the delay', async () => {
    vi.mocked(renderMarkdown).mockReturnValue('<p>**Hi**</p>');
    const { showPopover, statementHtml, onTriggerEnter } = useStatementPopover(() => '**Hi**');
    onTriggerEnter();
    await vi.advanceTimersByTimeAsync(500);
    expect(showPopover.value).toBe(true);
    expect(statementHtml.value).toBe('<p>**Hi**</p>');
    vi.mocked(renderMarkdown).mockClear();
  });

  it('does not show the popover when the statement is empty', async () => {
    const { showPopover, statementHtml, onTriggerEnter } = useStatementPopover(() => '');
    onTriggerEnter();
    await vi.advanceTimersByTimeAsync(500);
    expect(showPopover.value).toBe(false);
    expect(statementHtml.value).toBe('');
  });

  it('cancels a pending popover when leaving before the delay', async () => {
    const { showPopover, onTriggerEnter, onTriggerLeave } = useStatementPopover(() => 'x');
    onTriggerEnter();
    onTriggerLeave();
    await vi.advanceTimersByTimeAsync(500);
    expect(showPopover.value).toBe(false);
  });

  it('hides an already-shown popover on leave', async () => {
    const { showPopover, onTriggerEnter, onTriggerLeave } = useStatementPopover(() => 'x');
    onTriggerEnter();
    await vi.advanceTimersByTimeAsync(500);
    expect(showPopover.value).toBe(true);
    onTriggerLeave();
    expect(showPopover.value).toBe(false);
  });

  it('cleanup clears the timer and hides the popover', async () => {
    const { showPopover, onTriggerEnter, cleanup } = useStatementPopover(() => 'x');
    onTriggerEnter();
    await vi.advanceTimersByTimeAsync(500);
    cleanup();
    expect(showPopover.value).toBe(false);
  });

  it('does not show the popover and logs an error when the renderer throws', async () => {
    vi.mocked(renderMarkdown).mockImplementationOnce(() => { throw new Error('render failed'); });
    const { showPopover, statementHtml, onTriggerEnter } = useStatementPopover(() => '**Hi**');
    onTriggerEnter();
    await vi.advanceTimersByTimeAsync(500);
    expect(showPopover.value).toBe(false);
    expect(statementHtml.value).toBe('');
    expect(logger.error).toHaveBeenCalledOnce();
    vi.mocked(renderMarkdown).mockClear();
  });

  it('resets the timer on rapid re-entry so the popover shows only once', async () => {
    const { showPopover, onTriggerEnter } = useStatementPopover(() => 'x');
    onTriggerEnter();
    onTriggerEnter();
    await vi.advanceTimersByTimeAsync(500);
    expect(showPopover.value).toBe(true);
  });
});
