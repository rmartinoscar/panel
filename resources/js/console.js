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

window.Xterm = {
    createTerminal: function (options) {
        // window.Xterm.cleanupTerminal();

        const terminal = new Terminal({
            ...defaultOptions,
            ...options
        });
        const fitAddon = new FitAddon();
        const webLinksAddon = new WebLinksAddon();
        const searchAddon = new SearchAddon();
        const searchAddonBar = new SearchBarAddon({ searchAddon });
        const webglAddon = new WebglAddon();
        terminal.loadAddon(fitAddon);
        terminal.loadAddon(webLinksAddon);
        terminal.loadAddon(searchAddon);
        terminal.loadAddon(searchAddonBar);
        terminal.loadAddon(webglAddon);

        window.Xterm.webglAddon = webglAddon,
        window.Xterm.fitAddon = fitAddon,
        window.Xterm.webLinksAddon = webLinksAddon,
        window.Xterm.searchAddon = searchAddon,
        window.Xterm.searchAddonBar = searchAddonBar,

        terminal.attachCustomKeyEventHandler((event) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'c') {
                navigator.clipboard.writeText(terminal.getSelection());
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

        return {
            terminal,
            webglAddon,
            fitAddon,
            webLinksAddon,
            searchAddon,
            searchAddonBar,
        };
    },
    cleanupTerminal: function () {
        if (!window.Xterm) {
            return;
        }

        ['terminal', 'webglAddon', 'fitAddon', 'webLinksAddon', 'searchAddon', 'searchAddonBar']
        .forEach(element => {
            if (window.Xterm[element]) {
                window.Xterm[element].dispose();
                window.Xterm[element] = null;
                console.log(element + " disposed");
            }
        });

        if (window.Xterm.socket) {
            window.Xterm.socket.close();
            window.Xterm.socket = null;
            console.log("WebSocket closed");
        }
    }
};
