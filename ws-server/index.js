// Simple WebSocket relay server for CampusLink
// Usage: node index.js
const WebSocket = require('ws');
const url = require('url');

// Map of agentId -> ws connection(s)
const agents = new Map();

const wss = new WebSocket.Server({ port: 8081 });
console.log('WebSocket server listening on ws://localhost:8081');

function sendToAgent(agentId, payload) {
  const conns = agents.get(agentId);
  if (!conns) return 0;
  let sent = 0;
  for (const ws of conns) {
    if (ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(payload));
      sent++;
    }
  }
  return sent;
}

wss.on('connection', function connection(ws, req) {
  // Expect ?token=<session-token>&role=driver|rider&agent_id=...
  const q = url.parse(req.url, true).query;
  const agentId = q.agent_id;
  const role = q.role;
  const token = q.token;

  // Minimal token check: require token present. For production, verify token with server.
  if (!token || !agentId || !role) {
    ws.send(JSON.stringify({type:'error', message:'missing auth parameters'}));
    ws.close();
    return;
  }

  // attach metadata
  ws.agentId = agentId;
  ws.role = role;

  // register
  if (!agents.has(agentId)) agents.set(agentId, new Set());
  agents.get(agentId).add(ws);

  console.log('Agent connected', agentId, role);

  ws.on('close', () => {
    const set = agents.get(agentId);
    if (set) {
      set.delete(ws);
      if (set.size === 0) agents.delete(agentId);
    }
    console.log('Agent disconnected', agentId);
  });

  ws.on('message', (msg) => {
    try {
      const data = JSON.parse(msg);
      // handle pings or admin messages later
      if (data.type === 'ping') ws.send(JSON.stringify({type:'pong'}));
    } catch (e) {
      console.warn('Invalid message', e);
    }
  });
});

// Allow simple HTTP POST notifications via a tiny express-like handler using native HTTP
const http = require('http');
const PORT = 8082;

const server = http.createServer((req, res) => {
  if (req.method === 'POST' && req.url === '/notify') {
    let body = '';
    req.on('data', chunk => body += chunk.toString());
    req.on('end', () => {
      try {
        const payload = JSON.parse(body);
        const targetAgent = payload.agent_id;
        if (targetAgent) {
          const sent = sendToAgent(targetAgent, {type:'booking_request', data: payload});
          res.writeHead(200, {'Content-Type':'application/json'});
          res.end(JSON.stringify({sent}));
        } else {
          res.writeHead(400, {'Content-Type':'application/json'});
          res.end(JSON.stringify({error:'missing agent_id'}));
        }
      } catch (e) {
        res.writeHead(400, {'Content-Type':'application/json'});
        res.end(JSON.stringify({error:'invalid json'}));
      }
    });
  } else {
    res.writeHead(404); res.end();
  }
});

server.listen(PORT, () => console.log('WS relay HTTP endpoint listening on http://localhost:' + PORT + '/notify'));
