<?php

namespace Plugin\ActivityStreamsTwo\Util\Response;

use App\Entity\Note;
use Exception;
use Plugin\ActivityStreamsTwo\Util\Model\EntityToType\NoteToType;

abstract class NoteResponse
//class NoteResponse extends Controller
{
    /**
     * @param Note $note
     * @param int  $status The response status code
     *
     * @throws Exception
     *
     * @return TypeResponse
     */
    public static function handle(Note $note, int $status = 200): TypeResponse
    // public function handle(Request $request, int $id): JsonResponse
    {
        // $note = DB::findOneBy('note', ['id' => $id]);
        return new TypeResponse(data: NoteToType::translate($note), status: $status);
    }
}