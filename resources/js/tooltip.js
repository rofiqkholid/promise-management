/**
 * Global Fast Tooltip Engine for Promise Management
 * Automatically transforms [title] and [data-tooltip] attributes into sleek, responsive tooltips.
 * Handles continuous switching and reliable auto-dismissal when moving off tooltip elements.
 */

class TooltipManager {
    constructor() {
        this.coldDelay = 220;    // Initial delay for the first tooltip in ms
        this.warmDelay = 30;     // Brief buffer when switching between adjacent tooltips
        this.warmDuration = 300; // Grace period (ms) where switching elements remains in warm state
        this.showTimer = null;
        this.hideTimer = null;
        this.warmTimer = null;
        this.isWarm = false;
        this.activeElement = null;
        this.tooltipEl = null;
        this.arrowEl = null;
        this.textSpan = null;

        this.init();
    }

    init() {
        if (typeof document === 'undefined') return;

        // Create the singleton tooltip container in the DOM
        this.tooltipEl = document.createElement('div');
        this.tooltipEl.className = 'custom-tooltip-container';
        this.tooltipEl.setAttribute('role', 'tooltip');
        this.tooltipEl.setAttribute('aria-hidden', 'true');

        this.arrowEl = document.createElement('div');
        this.arrowEl.className = 'custom-tooltip-arrow';
        this.tooltipEl.appendChild(this.arrowEl);

        this.textSpan = document.createElement('span');
        this.textSpan.className = 'custom-tooltip-text';
        this.tooltipEl.appendChild(this.textSpan);

        document.body.appendChild(this.tooltipEl);

        // Global Event Delegation (capture phase for reliability)
        document.addEventListener('mouseover', (e) => this.handlePointerEnter(e), true);
        document.addEventListener('mouseout', (e) => this.handlePointerLeave(e), true);
        document.addEventListener('focusin', (e) => this.handlePointerEnter(e), true);
        document.addEventListener('focusout', (e) => this.handlePointerLeave(e), true);
        document.addEventListener('click', () => this.hideImmediately(), true);
        document.addEventListener('pointerdown', () => this.hideImmediately(), true);
        window.addEventListener('scroll', () => this.hideImmediately(), { passive: true });
        window.addEventListener('resize', () => this.hideImmediately(), { passive: true });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hideImmediately();
        });
    }

    findTooltipTarget(eventTarget) {
        if (!eventTarget || !(eventTarget instanceof Element)) return null;
        const target = eventTarget.closest('[title], [data-tooltip], [data-original-title]');
        if (!target) return null;

        const content = this.extractTooltipContent(target);
        return content ? target : null;
    }

    extractTooltipContent(element) {
        if (!element || !(element instanceof Element)) return '';

        // 1. Check data-tooltip first
        if (element.hasAttribute('data-tooltip')) {
            const val = element.getAttribute('data-tooltip');
            if (val && val.trim()) return val.trim();
        }

        // 2. Check native title (move to data-original-title to suppress browser default OS tooltip)
        if (element.hasAttribute('title')) {
            const text = element.getAttribute('title');
            if (text && text.trim()) {
                const trimmed = text.trim();
                element.setAttribute('data-original-title', trimmed);
                element.removeAttribute('title');
                return trimmed;
            }
        }

        // 3. Check previously stored data-original-title
        if (element.hasAttribute('data-original-title')) {
            const val = element.getAttribute('data-original-title');
            if (val && val.trim()) return val.trim();
        }

        return '';
    }

    handlePointerEnter(e) {
        const target = this.findTooltipTarget(e.target);

        // If we currently have an active tooltip
        if (this.activeElement) {
            // If cursor is still inside the current active element, do nothing
            if (this.activeElement.contains(e.target)) {
                return;
            }

            // If cursor moved to an element that has NO tooltip, immediately hide previous tooltip!
            if (!target) {
                clearTimeout(this.showTimer);
                this.hide();
                return;
            }
        }

        // If target doesn't exist, we have nothing to show
        if (!target) return;

        const content = this.extractTooltipContent(target);
        if (!content) {
            if (this.activeElement === target) this.hide();
            return;
        }

        clearTimeout(this.hideTimer);
        clearTimeout(this.showTimer);
        clearTimeout(this.warmTimer);

        if (this.activeElement === target && this.tooltipEl.classList.contains('is-visible')) {
            return;
        }

        this.activeElement = target;

        // If warm (tooltip currently visible or recently switched), switch smoothly with warmDelay
        const delay = (this.isWarm || this.tooltipEl.classList.contains('is-visible')) 
            ? this.warmDelay 
            : this.coldDelay;

        this.showTimer = setTimeout(() => {
            this.show(target, content);
        }, delay);
    }

    handlePointerLeave(e) {
        // If moving outside the current active element
        if (this.activeElement) {
            const related = e.relatedTarget;
            // If relatedTarget is null (cursor left window) or not inside activeElement, hide!
            if (!related || !this.activeElement.contains(related)) {
                clearTimeout(this.showTimer);
                this.hide();
            }
        }
    }

    show(element, content) {
        if (!element || !document.body.contains(element)) return;

        // Update text content
        this.textSpan.textContent = content;

        // Check custom theme
        const theme = element.getAttribute('data-tooltip-theme');
        this.tooltipEl.className = 'custom-tooltip-container is-visible' + (theme ? ` tooltip-${theme}` : '');
        this.tooltipEl.setAttribute('aria-hidden', 'false');

        // Position calculation
        const placement = element.getAttribute('data-tooltip-placement') || 'top';
        this.positionTooltip(element, placement);

        this.isWarm = true;
    }

    positionTooltip(element, preferredPlacement) {
        const targetRect = element.getBoundingClientRect();
        const tooltipRect = this.tooltipEl.getBoundingClientRect();
        const spacing = 7;

        let placement = preferredPlacement;
        let top = 0;
        let left = 0;

        const fitsTop = targetRect.top - tooltipRect.height - spacing > 0;
        const fitsBottom = targetRect.bottom + tooltipRect.height + spacing < window.innerHeight;
        const fitsLeft = targetRect.left - tooltipRect.width - spacing > 0;
        const fitsRight = targetRect.right + tooltipRect.width + spacing < window.innerWidth;

        // Auto flip if bounds exceeded
        if (placement === 'top' && !fitsTop && fitsBottom) placement = 'bottom';
        else if (placement === 'bottom' && !fitsBottom && fitsTop) placement = 'top';
        else if (placement === 'left' && !fitsLeft && fitsRight) placement = 'right';
        else if (placement === 'right' && !fitsRight && fitsLeft) placement = 'left';

        switch (placement) {
            case 'bottom':
                top = targetRect.bottom + spacing;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'left':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.left - tooltipRect.width - spacing;
                break;
            case 'right':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.right + spacing;
                break;
            case 'top':
            default:
                placement = 'top';
                top = targetRect.top - tooltipRect.height - spacing;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
        }

        // Clamp inside window boundaries horizontally
        const padding = 8;
        if (left < padding) left = padding;
        if (left + tooltipRect.width > window.innerWidth - padding) {
            left = window.innerWidth - tooltipRect.width - padding;
        }

        // Clamp inside window boundaries vertically
        if (top < padding) top = padding;
        if (top + tooltipRect.height > window.innerHeight - padding) {
            top = window.innerHeight - tooltipRect.height - padding;
        }

        this.tooltipEl.setAttribute('data-placement', placement);
        this.tooltipEl.style.top = `${Math.round(top)}px`;
        this.tooltipEl.style.left = `${Math.round(left)}px`;
    }

    hide() {
        clearTimeout(this.hideTimer);
        this.hideTimer = setTimeout(() => {
            this.hideImmediately();
            
            // Keep warm state for a brief window so moving quickly to a nearby tooltip is smooth
            this.isWarm = true;
            clearTimeout(this.warmTimer);
            this.warmTimer = setTimeout(() => {
                this.isWarm = false;
            }, this.warmDuration);
        }, 50);
    }

    hideImmediately() {
        clearTimeout(this.showTimer);
        clearTimeout(this.hideTimer);
        if (this.tooltipEl) {
            this.tooltipEl.classList.remove('is-visible');
            this.tooltipEl.setAttribute('aria-hidden', 'true');
        }
        this.activeElement = null;
    }
}

// Auto-initialize on DOM ready
if (typeof window !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.globalTooltip = new TooltipManager();
        });
    } else {
        window.globalTooltip = new TooltipManager();
    }
}

export default TooltipManager;
