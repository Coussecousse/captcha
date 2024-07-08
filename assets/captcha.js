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
        const puzzleBar = this.getAttribute('puzzle-bar') || 'left';


        this.classList.add('captcha');
        if (puzzleBar === 'right' || puzzleBar === 'left') {
            this.classList.add('puzzle-bar-lr');
        } else {
            this.classList.add('puzzle-bar-tb');
        }

        switch (puzzleBar) {
            case 'left':
                this.classList.add('puzzle-bar-left');
                break;
            case 'right':
                this.classList.add('puzzle-bar-right');
                break;
            case 'top' :
                this.classList.add('puzzle-bar-top');
                break;
            case 'bottom':
                this.classList.add('puzzle-bar-bottom');
                break;
            default : 
                this.classList.add('puzzle-bar-left');
                break;
        }

        this.style.setProperty('--image', `url(${this.getAttribute('src')})`);
        this.style.setProperty('--width', `${width}px`);
        this.style.setProperty('--height', `${height}px`);
        this.style.setProperty('--pieceWidth', `${pieceWidth}px`);
        this.style.setProperty('--pieceHeight', `${pieceHeight}px`);

        const inputs = this.querySelectorAll('.captcha-answer');
        
        let isDragging = false;
        const piecesImagePostition = [
            'top right',
            'bottom right',
            'top left',
        ]

        const piecesContainer = this.querySelector('.captcha-pieces-container');

        for (let i = 0; i < numberOfPieces; i++) {
            const pieceContainer = document.createElement('div');
            pieceContainer.classList.add('piece-container');

            const piece = document.createElement('div');
            piece.id = `piece-${i+1}`;
            piece.classList.add('captcha-piece', 'piece-waiting-interaction');

            pieceContainer.appendChild(piece);
            piecesContainer.appendChild(pieceContainer);

            function onPointerMove(e) {
                if (!isDragging) return;
                piece.style.setProperty('position', 'absolute');
                if (puzzleBar == 'left' || puzzleBar == 'right') {
                    position.x = clamp(position.x + e.movementX, 0, width );
                    position.y = clamp(position.y + e.movementY, 0, maxY);
                } else {
                    position.x = clamp(position.x + e.movementX, 0, maxX);
                    position.y = clamp(position.y + e.movementY, 0, height);
                }

                piece.style.setProperty('transform', `translate(${position.x}px, ${position.y}px)` )

                let input;
                switch(piece.id) {
                    case 'piece-2':
                        input = Array.from(inputs).find(input => input.name.includes('answer_1'));
                        break;
                    case 'piece-3':
                        input = Array.from(inputs).find(input => input.name.includes('answer_2'));
                        break;
                    case 'piece-1':
                        input = Array.from(inputs).find(input => input.name.includes('answer_3'));
                    default:
                        break;
                }
                
                if (puzzleBar === 'left') {
                    input.value = `${position.x - pieceWidth}-${position.y}`;
                } else if (puzzleBar === 'top') {
                    input.value = `${position.x}-${position.y - pieceHeight}`;
                } else {
                    input.value = `${position.x}-${position.y}`;
                }
            }

            const containerDomrect = piecesContainer.getBoundingClientRect();

            let rectPiece = piece.getBoundingClientRect();
            
            let position;
            if (puzzleBar == 'left') {
                position = {x: rectPiece.x - containerDomrect.x, y: rectPiece.y - containerDomrect.y};
            } else if (puzzleBar === 'right') {
                position = {x: rectPiece.x - pieceWidth, y: rectPiece.y - containerDomrect.y};
            } else if (puzzleBar === 'top') {
                position = {x: rectPiece.x - containerDomrect.x, y: rectPiece.y - containerDomrect.y};
            } else if (puzzleBar === 'bottom') {
                position = {x: rectPiece.x - containerDomrect.x, y: rectPiece.y};
            }

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