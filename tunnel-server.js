// tunnel-server.js

const http = require('http');
const WebSocket = require('ws');

const clients = new Map();

const server = http.createServer(async (req, res) => {
  const clientId = req.url.split('/')[1]; // e.g., /myapp
  const clientSocket = clients.get(clientId);

  if (!clientSocket) {
    res.writeHead(502);
    return res.end('Tunnel client not connected.');
  }

  let body = '';
  req.on('data', chunk => body += chunk);
  req.on('end', () => {
    const requestId = Date.now().toString();

    function onMessage(message) {
      const response = JSON.parse(message);
      if (response.requestId === requestId) {
        res.writeHead(response.status, response.headers);
        res.end(response.body);
        clientSocket.off('message', onMessage);
      }
    }

    clientSocket.on('message', onMessage);

    clientSocket.send(JSON.stringify({
      type: 'http-request',
      requestId,
      method: req.method,
      path: req.url,
      headers: req.headers,
      body
    }));
  });
});

const wss = new WebSocket.Server({ server });

wss.on('connection', ws => {
  ws.on('message', msg => {
    const data = JSON.parse(msg);
    if (data.type === 'register') {
      clients.set(data.clientId, ws);
      ws.on('close', () => clients.delete(data.clientId));
      console.log(`Tunnel client "${data.clientId}" registered.`);
    }
  });
});

const PORT = 8080;
server.listen(PORT, () => {
  console.log(`Tunnel server is live on http://127.0.0.1:${PORT}`);
});
