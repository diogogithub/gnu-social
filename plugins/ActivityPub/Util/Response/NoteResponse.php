<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Response;

use App\Entity\Note;
use Exception;
use Plugin\ActivityPub\Util\Model\EntityToType\NoteToType;

abstract class NoteResponse
//class NoteResponse extends Controller
{
    /**
     * @param int $status The response status code
     *
     * @throws Exception
     */
    public static function handle(Note $note, int $status = 200): TypeResponse
    // public function handle(Request $request, int $id): JsonResponse
    {
        // $note = DB::findOneBy('note', ['id' => $id]);
        return new TypeResponse(data: NoteToType::translate($note), status: $status);
    }
}
