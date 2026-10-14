/**
 * drag-drop.js
 * Xử lý kéo thả sắp xếp các bước quy trình (DRAG_DROP_SEQUENCE)
 * Hỗ trợ cả thao tác Kéo thả chuột/cảm ứng VÀ các nút Mũi tên Lên/Xuống cho công nhân đeo găng tay
 */

class SequenceSorter {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;
        this.inputTarget = document.getElementById(options.inputId);
        this.onUpdate = options.onUpdate || function() {};

        this.init();
    }

    init() {
        this.items = Array.from(this.container.querySelectorAll('.sequence-item'));
        this.bindEvents();
        this.updateValue();
    }

    bindEvents() {
        let draggedItem = null;

        this.items.forEach(item => {
            // Drag and drop HTML5
            item.setAttribute('draggable', 'true');

            item.addEventListener('dragstart', (e) => {
                draggedItem = item;
                setTimeout(() => item.classList.add('opacity-40'), 0);
            });

            item.addEventListener('dragend', () => {
                draggedItem.classList.remove('opacity-40');
                draggedItem = null;
                this.updateValue();
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                const current = e.currentTarget;
                if (current !== draggedItem) {
                    const bounding = current.getBoundingClientRect();
                    const offset = bounding.y + (bounding.height / 2);
                    if (e.clientY - offset > 0) {
                        current.after(draggedItem);
                    } else {
                        current.before(draggedItem);
                    }
                }
            });

            // Touch-friendly Up / Down Arrow buttons
            const btnUp = item.querySelector('.btn-move-up');
            const btnDown = item.querySelector('.btn-move-down');

            if (btnUp) {
                btnUp.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const prev = item.previousElementSibling;
                    if (prev) {
                        item.parentNode.insertBefore(item, prev);
                        this.updateValue();
                        this.animateItem(item);
                    }
                });
            }

            if (btnDown) {
                btnDown.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const next = item.nextElementSibling;
                    if (next) {
                        item.parentNode.insertBefore(next, item);
                        this.updateValue();
                        this.animateItem(item);
                    }
                });
            }
        });
    }

    animateItem(item) {
        item.classList.add('bg-cyan-900/40', 'border-cyan-500');
        setTimeout(() => {
            item.classList.remove('bg-cyan-900/40', 'border-cyan-500');
        }, 300);
    }

    updateValue() {
        const currentItems = Array.from(this.container.querySelectorAll('.sequence-item'));
        const order = currentItems.map((el, idx) => {
            const badge = el.querySelector('.step-index-badge');
            if (badge) badge.innerText = `${idx + 1}`;
            return el.dataset.stepId;
        });

        if (this.inputTarget) {
            this.inputTarget.value = JSON.stringify({ submitted_order: order });
        }
        this.onUpdate(order);
    }
}

