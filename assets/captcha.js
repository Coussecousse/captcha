function clamp(n, min, max) {
    return Math.min(Math.max(n, min), max);
}

function randomNumberBetween(min, max) {
    return Math.floor(Math.random() * (max-min + 1) + min);
}

function widthRangesForPieces(width, numberOfPieces, spaceBetweenPieces) {
    let lastMinWidth = 0;
    const ranges = [];
    const imageDivision = (width - (spaceBetweenPieces * numberOfPieces)) / numberOfPieces;

    for (let i = 0; i < numberOfPieces; i++) {
        const nextWidth = lastMinWidth + imageDivision;
        ranges.push([lastMinWidth, nextWidth]);
        lastMinWidth = nextWidth + spaceBetweenPieces; 
    }

    return ranges;
}

class PuzzleCaptcha extends HTMLElement
{

    connectedCallback() {
        const width = parseInt(this.getAttribute('width'), 10);
        const height = parseInt(this.getAttribute('height'), 10);
        const pieceWidth = parseInt(this.getAttribute('piece-width'), 10);
        const pieceHeight = parseInt(this.getAttribute('piece-height'), 10);
        const maxX = width - pieceWidth;
        const maxY = height - pieceHeight; 
        const numberOfPieces = parseInt(this.getAttribute('pieces-number')) || 1;
        const spaceBetweenPieces = parseInt(this.getAttribute('space-between-pieces')) || 0;

        this.classList.add('captcha');
        this.style.setProperty('--image', `url(${this.getAttribute('src')})`);
        this.style.setProperty('--width', `${width}px`);
        this.style.setProperty('--height', `${height}px`);
        this.style.setProperty('--pieceWidth', `${pieceWidth}px`);
        this.style.setProperty('--pieceHeight', `${pieceHeight}px`);

        const input = this.querySelector('.captcha-answer');
        let isDragging = false;
        const piecesImagePostition = [
            'top right',
            'bottom right',
            'top left',
        ]
        for (let i = 0; i < numberOfPieces; i++) {
            const piece = document.createElement('div');
            piece.id = `piece-${i+1}`;
            piece.classList.add('captcha-piece', 'piece-waiting-interaction');
            this.appendChild(piece);

            let ranges = widthRangesForPieces(width, numberOfPieces, spaceBetweenPieces)[i];
            
            function onPointerMove(e) {
                if (!isDragging) return;
                position.x = clamp(position.x + e.movementX, 0, maxX);
                position.y = clamp(position.y + e.movementY, 0, maxY);
                piece.style.setProperty('transform', `translate(${position.x}px, ${position.y}px)` )
                input.value = `${position.x}-${position.y}`;
            }

            let position = {x: randomNumberBetween(ranges[0], ranges[1]), y: randomNumberBetween(0, maxY)};

            piece.style.setProperty('transform', `translate(${position.x}px, ${position.y}px)`);
            piece.style.setProperty('background-position', `${piecesImagePostition[i]}`);

            piece.addEventListener('pointerdown', e => {
                isDragging = true;
                document.body.style.setProperty('user-select', 'none');
                piece.classList.add('is-moving');
    
                window.addEventListener('pointerup', () => {
                    isDragging = false;
                    document.body.style.removeProperty('user-select');
                    if (piece.classList.contains('piece-waiting-interaction')) {
                        piece.classList.remove('piece-waiting-interaction');
                    }
                    piece.classList.remove('is-moving');
                    this.removeEventListener('pointermove', onPointerMove);
                }, {once: true})

                this.addEventListener('pointermove', onPointerMove);
            })
        }
    }
}

customElements.define('puzzle-captcha', PuzzleCaptcha)