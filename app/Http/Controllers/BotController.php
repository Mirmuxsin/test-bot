<?php

namespace App\Http\Controllers;

use App\Jobs\TaimerJob;
use App\Models\Answer;
use App\Models\Option;
use App\Models\Question;
use App\Models\Student;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Milly\Laragram\FSM\FSM;
use Milly\Laragram\Laragram;
use Milly\Laragram\Types\CallbackQuery;
use Milly\Laragram\Types\Message;
use Milly\Laragram\Types\Update;

class BotController extends Controller
{
    public static function start(Update $update)
    {
        $admins = [956158960, 5021858904, 407482241];
        FSM::update('login');

        if (in_array($update->message->from->id, $admins)) {
            FSM::update('admin');
        }

        if ($update->message->text == '/start') {
            return Laragram::sendMessage([
                'text' => "login va parolni kriting:\n\n(namuna: Abduvali parol001)",
                'chat_id' => $update->message->from->id
            ]);
        }
    }

    public static function admin(Update $update) {
        if ($update->message->text == "/start") {
            Laragram::sendMessage([
                'text' => "/newuser {ism} {parol} - Yangi foydalanuvchi qo'shish
Masalan: /newuser Ahmadjon parol001

/newtest {savollarSoni} - Yangi test qo'shish
savollarSoni - Har bir foydalanuvchiga tushadigan savollar soni
Masalan: /test 10

/results {test_id} - Test natijalarini ko'rish
/users - Barcha foydalanuvchilarni ko'rish
/tests - Barcha savollar soni

Test ga savollarni qo'shish:

test id {test_id}

Savol {savol}
+{togri variant 1}
{variant 2}
{variant 3}
{variant 4}

Savol {savol2}
{variant 1}
+{togri variant 2}
{variant 3}
{variant 4}",
                'chat_id' => $update->message->from->id
            ]);
        }

        if (str_contains($update->message->text, '/newuser ')) {
            $user_name = explode(' ', $update->message->text);
            if (count($user_name) != 3) {
                return Laragram::sendMessage([
                    'chat_id' => $update->message->from->id,
                    'text' => "Yangi userni quyidagi korinishda kiriting:\n\n/newuser Ism Parol"
                ]);
            }
            $user = new Student();
            $user->name = $user_name[1];
            $user->password = $user_name[2];
            $user->save();
            return Laragram::sendMessage([
                'chat_id' => $update->message->from->id,
                'text' => "Saqlandi!\n\nId: " . $user->id . " \nName: " . $user->name . "\nPassword: " . $user->password
            ]);
        }

        if ($update->message->text == "/users") {
            $text = "";
            foreach (Student::with('answers')->get() as $user) {
                $text .= "\n\nId: " . $user->id . " \nName: " . $user->name . "\nPassword: " . $user->password;


                if ($user->answers->count() > 0) {
                    foreach ($user->answers()->groupBy('test_id')->get() as $key => $test) {
                        $text .= json_encode($test);
                    }
                }
            }
            return Laragram::sendMessage([
                'chat_id' => $update->message->from->id,
                'text' => $text
            ]);
        }

        if (str_contains($update->message->text, '/results ')) {
            $id = explode('/results ', $update->message->text)[1];
            $questions = Test::findOrFail($id)->questions->whereNotNull('student_id');
//
//            return Laragram::sendMessage([
//                'chat_id' => $update->message->from->id,
//                'text' => json_encode($questions)
//            ]);

            $array = [];
            foreach ($questions as $question) {
                $array[$question->student_id]['name'] = $question->user->name;
                $array[$question->student_id]['answers'] = $questions->where(
                    'student_id', $question->student_id
                    )->count();
                if ($question->answer) {
                    if ($question->answer->is_true) {
                        if (isset($array[$question->student_id]['is_true'])) {
                            $array[$question->student_id]['is_true'] += 1;
                        } else {
                            $array[$question->student_id]['is_true'] = 1;
                        }
                    }
                }
            }
            $text = "Test id ".$id;
            foreach ($array as $item) {
                $text .= "\n\n".$item['name']. "\nJami javoblar: ".$item['answers']."\nTogri javoblar: ".$item['is_true'];
            }

            return Laragram::sendMessage([
                'chat_id' => $update->message->from->id,
                'text' => $text
            ]);
        }

        if ($update->message->text == "/tests") {
            $text = "";
            $tests = Test::all();
            if ($tests->count() == 0) {
                return Laragram::sendMessage([
                    'chat_id' => $update->message->from->id,
                    'text' => "Testlar hali qo'shilmagan!"
                ]);
            }
            foreach ($tests as $user) {
                $text .= "\n\nId: " . $user->id . " \nSavollar soni: " . $user->count . "\nBazadagi jami savollar: " . $user->questions()->count();
            }
            return Laragram::sendMessage([
                'chat_id' => $update->message->from->id,
                'text' => $text
            ]);
        }

        if (str_contains($update->message->text, '/newtest ')) {
            $count = explode('/newtest ', $update->message->text)[1];
            $test = new Test();
            $test->count = $count;
            $test->save();
            return Laragram::sendMessage([
                'chat_id' => $update->message->from->id,
                'text' => "Test yaratildi!\n\nID: " . $test->id
            ]);
        }

        if (str_contains($update->message->text, 'test id ')) {
            $explode = explode("\n\n", $update->message->text);
            foreach ($explode as $lines) {
                foreach (explode("\n", $lines) as $key => $line) {

                    if (str_contains($line, 'test id ')) {
                        $test_id = explode('test id ', $line)[1];
                        if (!Test::find($test_id)) {
                            return Laragram::sendMessage([
                                'chat_id' => $update->message->from->id,
                                'text' => $test_id." id li test topilmadi!"
                            ]);
                        }
                        return Laragram::sendMessage([
                            'chat_id' => $update->message->from->id,
                            'text' => "Xabarning eng tepasida test id ni biriktiring! Namuna:\n\ntest id {id}"
                        ]);
                    }
                    if (str_contains($line, 'Savol') and isset($test_id)) {
                        $savol = explode('Savol', $line)[1];
                        $question = new Question();
                        $question->title = $savol;
                        $question->test_id = $test_id;
                        $question->save();
                        $question_id = $question->id;
                    } else {
                        if (isset($question_id)) {
                            $option = new Option();
                            $option->text = $line;
                            $option->question_id = $question_id;
                            if (str_contains($line, "+")) {
                                $option->is_true = true;
                                $option->text = str_replace('+', '', $line);
                            }
                            $option->save();
                        }
                    }

                }
            }

            return Laragram::sendMessage([
                'chat_id' => $update->message->from->id,
                'text' => "saved!"
            ]);
        }
    }

    public static function login(Message $message)
    {
        if (!str_contains($message->text, " ")) {
            return Laragram::sendMessage([
                'chat_id' => $message->from->id,
                'text' => "Login va parolni o'rtasida probel bilan kiriting!\n\nNamuna: Abduvali parol001"
            ]);
        }
        $text = explode(" ", $message->text);
        $student = Student::where([
            'name' => $text[0],
            'password' => $text[1]
        ]);
        if ($student->count() == 0) {
            return Laragram::sendMessage([
                'chat_id' => $message->from->id,
                'text' => 'Foydalanuvchi topilmadi! Login va parolni tekshirib, qaytadan kiriting'
            ]);
        }
        $student = $student->first();
        $student->telegram_id = $message->from->id;
        $student->save();

        FSM::update('test');
        return self::sendTest($student);

    }

    public static function test(CallbackQuery|Message $query)
    {
        if (isset($query->text)) {
            $student = Student::where('telegram_id', $query->from->id)->first();
            return self::sendTest($student);
        }

        $student = Student::where('telegram_id', $query->from->id)->first();

        $explode = explode(" ", $query->data);
        $question = Question::where([
            'id' => $explode[1],
            'student_id' => $student->id,
            'is_answered' => 0
        ])->first();
        $option = Option::where([
            'question_id' => $question->id,
            'id' => $explode[2]
        ])->first();

        $answer = new Answer();
        $answer->student_id = $student->id;
        $answer->question_id = $question->id;
        $answer->is_true = $option->is_true;
        $answer->test_id = $question->test_id;
        $answer->option_id = $option->id;
        $answer->save();

        Laragram::deleteMessage([
            'chat_id' => $student->telegram_id,
            'message_id' => $query->message->message_id
        ]);

        return self::sendTest($student);
    }

    /**
     * @param Student $student
     * @return array|Model|Boolean
     */
    public static function sendTest(Student $student)
    {
        $test = Test::latest()->first();
        $count = $test->count;

        $answers = Answer::query()->where([
            'student_id' => $student->id,
            'test_id' => $test->id
        ]);

        if ($answers->count() <= $count) {

            $question = $test->questions()->where([
                'is_answered' => 0,
                'student_id' => null
            ]);

            if ($question->count() == 0) {
                return Laragram::sendMessage([
                    'chat_id' => $student->telegram_id,
                    'text' => "Savollar qolmagan, savollar qo'shilishini kuting iltimos"
                ]);
            }

            $question = $question->inRandomOrder()->first();

            $options = $question->options()->inRandomOrder()->get();

            $array = [];
            foreach ($options as $option) {
                $array[] = [
                    ['text' => $option->text, 'callback_data' => 'question ' . $question->id . ' ' . $option->id]
                ];
            }

            $send = Laragram::sendMessage([
                'chat_id' => $student->telegram_id,
                'text' => $question->title,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $array
                ])
            ]);

            TaimerJob::dispatch($send['result']['chat']['id'], $send['result']['message_id'], $student->telegram_id)->delay(10);

            $question->student_id = $student->id;
            return $question->save();
        } else {
            Laragram::sendMessage([
                'chat_id' => $student->telegram_id,
                'text' => 'Siz yetarlicha savollarga javob berib boldingiz! Yangi test qoshilishini kuting!'
            ]);

            return false;
        }
    }
}
