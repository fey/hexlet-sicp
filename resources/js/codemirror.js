require('codemirror/mode/commonlisp/commonlisp.js');
const CodeMirror = require('codemirror');
const parinferCodeMirror = require('parinfer-codemirror');

const textArea = document.body.querySelector('#x-editor-textarea');
const editor = CodeMirror.fromTextArea(textArea, {
  lineNumbers: true,
  mode: 'text/x-common-lisp'
});
parinferCodeMirror.init(editor);

editor.on('change', () => {
  // textArea.innerText = editor.getValue();
  editor.save();
});
