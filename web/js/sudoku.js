console.log('Sudoku client started');
var conn = new WebSocket('ws://localhost:8080');
conn.onmessage = function(e) {
    console.log('Response:' + e.data);
};
conn.onopen = function(e) {
    console.log("Connection established!");
    console.log('Hey!');
    conn.send('Hey!');
};