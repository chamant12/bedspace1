<?php

namespace App\Http\Controllers;

use App\Chat\GuestTools;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * EpickTours MCP server (Model Context Protocol), Streamable HTTP transport in
 * stateless JSON mode: clients POST JSON-RPC 2.0 messages to /api/mcp.
 *
 * Exposes the read-only guest tools (App\Chat\GuestTools): destinations,
 * property search, property details, availability with prices and booking
 * links, cancellation policy, and booking lookup (reference + email).
 * Any MCP client (Claude, MCP Inspector, other agents) can connect.
 *
 * Set MCP_TOKEN to require "Authorization: Bearer <token>".
 */
class McpController extends Controller
{
    const SUPPORTED_VERSIONS = ['2025-11-25', '2025-06-18', '2025-03-26'];

    public function __construct(private GuestTools $tools)
    {
    }

    public function handle(Request $request): JsonResponse|Response
    {
        $token = config('bedspace.mcp_token');
        if ($token && !hash_equals($token, (string) $request->bearerToken())) {
            return response()->json($this->error(null, -32001, 'Unauthorized'), 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return response()->json($this->error(null, -32700, 'Parse error'), 400);
        }

        // A batch is a list of messages; notifications get no response
        $isBatch = array_is_list($payload);
        $responses = array_values(array_filter(array_map(fn ($message) => $this->dispatch($message), $isBatch ? $payload : [$payload])));

        if (!$responses) {
            return response()->noContent(202);
        }

        return response()->json($isBatch ? $responses : $responses[0]);
    }

    // No server-initiated stream (GET) or sessions (DELETE) in stateless mode
    public function notAllowed(): Response
    {
        return response('', 405)->header('Allow', 'POST');
    }

    private function dispatch(mixed $message): ?array
    {
        if (!is_array($message) || ($message['jsonrpc'] ?? null) !== '2.0' || !isset($message['method'])) {
            return $this->error($message['id'] ?? null, -32600, 'Invalid Request');
        }

        $id = $message['id'] ?? null;
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];

        if (!array_key_exists('id', $message)) {
            return null; // notification (e.g. notifications/initialized)
        }

        return match ($message['method']) {
            'initialize' => $this->result($id, [
                'protocolVersion' => in_array($params['protocolVersion'] ?? null, self::SUPPORTED_VERSIONS, true)
                    ? $params['protocolVersion'] : self::SUPPORTED_VERSIONS[0],
                'capabilities' => ['tools' => ['listChanged' => false]],
                'serverInfo' => ['name' => 'bedspace', 'title' => 'EpickTours hotel information', 'version' => '1.0.0'],
                'instructions' => 'Read-only information about EpickTours properties in Sri Lanka: search by area and dates, '
                    .'property details, rooms, meal plans and prices (LKR or USD), cancellation policies, and a guest\'s own '
                    .'booking by reference + email. Nothing can be booked or changed through these tools; give guests the '
                    .'returned URLs to book. Dates are YYYY-MM-DD in Sri Lanka time.',
            ]),
            'ping' => $this->result($id, new \stdClass()),
            'tools/list' => $this->result($id, ['tools' => $this->tools->definitions()]),
            'tools/call' => $this->callTool($id, $params),
            default => $this->error($id, -32601, 'Method not found'),
        };
    }

    private function callTool(mixed $id, array $params): array
    {
        $name = (string) ($params['name'] ?? '');
        $known = array_column($this->tools->definitions(), 'name');
        if (!in_array($name, $known, true)) {
            return $this->error($id, -32602, "Unknown tool: $name");
        }

        $result = $this->tools->call($name, is_array($params['arguments'] ?? null) ? $params['arguments'] : []);

        return $this->result($id, [
            'content' => [['type' => 'text', 'text' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]],
            'structuredContent' => $result,
            'isError' => isset($result['error']),
        ]);
    }

    private function result(mixed $id, mixed $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
