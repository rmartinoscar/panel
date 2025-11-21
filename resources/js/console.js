import { Terminal } from '@xterm/xterm';
import { FitAddon } from '@xterm/addon-fit';
import { WebLinksAddon } from '@xterm/addon-web-links';
import { SearchAddon } from '@xterm/addon-search';
import { SearchBarAddon } from 'xterm-addon-search-bar';
import { WebglAddon } from '@xterm/addon-webgl';

const defaultOptions = {
    lineHeight: 1.2,
    disableStdin: true,
    cursorStyle: 'underline',
    cursorInactiveStyle: 'underline',
    allowTransparency: true,
    theme: {
        background: 'rgba(19,26,32,0.7)',
        cursor: 'transparent',
        black: '#000000',
        red: '#E54B4B',
        green: '#9ECE58',
        yellow: '#FAED70',
        blue: '#396FE2',
        magenta: '#BB80B3',
        cyan: '#2DDAFD',
        white: '#d0d0d0',
        brightBlack: 'rgba(255, 255, 255, 0.2)',
        brightRed: '#FF5370',
        brightGreen: '#C3E88D',
        brightYellow: '#FFCB6B',
        brightBlue: '#82AAFF',
        brightMagenta: '#C792EA',
        brightCyan: '#89DDFF',
        brightWhite: '#ffffff',
        selection: '#FAF089'
    }
};

window.console = {
    prelude: '',
    wire: null,
    setupWidget,
};

function setupWidget(url, serverUuid, prelude, wire, options) {
    setupConsole(options);
    setupWebsocket(url, serverUuid, prelude, wire);
}

function setupConsole(options) {
    if (window.console.div = document.getElementById('terminal')) {
        window.console.div.innerHTML = '';
    }

    options = { ...defaultOptions, ...options };

    window.console.terminal = new Terminal(options);
    const fitAddon = new FitAddon();
    const webLinksAddon = new WebLinksAddon();
    const searchAddon = new SearchAddon();
    const searchAddonBar = new SearchBarAddon({ searchAddon });
    const webglAddon = new WebglAddon();

    window.console.terminal.loadAddon(fitAddon);
    window.console.terminal.loadAddon(webLinksAddon);
    window.console.terminal.loadAddon(searchAddon);
    window.console.terminal.loadAddon(searchAddonBar);
    window.console.terminal.loadAddon(webglAddon);

    window.console.terminal.open(window.console.div);

    fitAddon.fit(); // Fixes SPA issues.

    window.addEventListener('load', () => {
        fitAddon.fit();
    });

    window.addEventListener('resize', () => {
        fitAddon.fit();
    });

    window.console.terminal.attachCustomKeyEventHandler((event) => {
        if ((event.ctrlKey || event.metaKey) && event.key === 'c') {
            navigator.clipboard.writeText(window.console.terminal.getSelection());
            return false;
        } else if ((event.ctrlKey || event.metaKey) && event.key === 'f') {
            event.preventDefault();
            searchAddonBar.show();
            return false;
        } else if (event.key === 'Escape') {
            searchAddonBar.hidden();
        }
        return true;
    });
}

function setupWebsocket(url, serverUuid, prelude, wire) {
    window.console.prelude = prelude;
    window.console.wire = wire;

    if (window.console.socket) {
        window.console.wire.dispatchSelf('token-request');
    } else {
        window.console.socket = new WebSocket(url);
    }

    window.console.socket.onopen = (event) => window.console.wire.dispatchSelf('token-request');
    window.console.socket.onerror = (event) => window.console.wire.dispatchSelf('websocket-error');
    window.console.socket.onmessage = (message) => handleSocketMessage(message);

    window.Livewire.on('setServerState', ({ state, uuid }) => serverUuid === uuid && handlePowerAction(state));

    window.console.wire.on('sendAuthRequest', ({ token }) => {
        window.console.socket.send(JSON.stringify({
            'event': 'auth',
            'args': [token]
        }));
    });

    window.console.wire.on('sendServerCommand', ({ command }) => {
        window.console.socket.send(JSON.stringify({
            'event': 'send command',
            'args': [command]
        }));
    });
}

const handleSocketMessage = (message) => {
    let { event, args } = JSON.parse(message.data);

    switch (event) {
        case 'console output':
        case 'install output':
            handleConsoleOutput(args[0]);
            break;
        case 'feature match':
            window.Livewire.dispatch('mount-feature', { data: args[0] });
            break;
        case 'status':
            handlePowerChangeEvent(args[0]);

            window.console.wire.dispatch('console-status', { state: args[0] });
            break;
        case 'transfer status':
            handleTransferStatus(args[0]);
            break;
        case 'daemon error':
            handleDaemonErrorOutput(args[0]);
            break;
        case 'stats':
            window.console.wire.dispatchSelf('store-stats', { data: args[0] });
            break;
        case 'auth success':
            window.console.socket.send(JSON.stringify({
                'event': 'send logs',
                'args': [null]
            }));
            break;
        case 'token expiring':
        case 'token expired':
            window.console.wire.dispatchSelf('token-request');
            break;
    }
};

const writeln = (line) =>
    window.console.terminal.writeln(window.console.prelude + line + '\u001b[0m');

const handleConsoleOutput = (line) =>
    writeln(line.replace(/(?:\r\n|\r|\n)$/im, ''));

const handleTransferStatus = (status) =>
    status === 'failure' && writeln('Transfer has failed.');

const handleDaemonErrorOutput = (line) =>
    writeln('\u001b[1m\u001b[41m' + line.replace(/(?:\r\n|\r|\n)$/im, ''));

const handlePowerChangeEvent = (state) =>
    writeln('Server marked as ' + state + '...');

const handlePowerAction = (state) => {
    window.console.socket.send(JSON.stringify({
        'event': 'set state',
        'args': [state]
    }));
}
