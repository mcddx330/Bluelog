<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class MakeAdminUser extends Command {
    protected $signature = 'bluelog:make-admin
        {--handle= : 対象のハンドル名}
        {--did= : 対象のdid}
    ';
    protected $description = '指定されたhandleまたはDIDのユーザーを管理者に設定します。';

    public function handle() {
        $handle = $this->option('handle');
        $did = $this->option('did');

        $user = null; // $userを初期化

        if ($handle || $did) {
            $user_query = User::query();
            $user = $user_query->where('handle', $handle);
            if ($did) {
                $user = $user_query->where('did', $did);
            }
            $user = $user->first();

            if (!$user) {
                $this->error('指定されたユーザーが見つかりませんでした。');

                return Command::FAILURE;
            }
        } else {
            $users = User::all(['id', 'handle', 'did', 'is_admin']);

            if ($users->isEmpty()) {
                $this->info('システムにユーザーが存在しません。');

                return Command::SUCCESS;
            }

            $headers = ['No.', 'Handle', 'DID', 'Admin'];
            $rows = [];
            foreach ($users as $index => $u) {
                $rows[] = [
                    $index + 1,
                    $u->handle,
                    $u->did,
                    $u->is_admin ? '*' : '-',
                ];
            }

            $this->table($headers, $rows);

            $selection = $this->ask('管理者に設定するユーザーの番号、handle、またはDIDを入力してください');

            if (is_numeric($selection)) {
                $index = (int)$selection - 1;
                if (isset($users[$index])) {
                    $user = $users[$index];
                }
            } else {
                $user = User::where('handle', $selection)
                    ->orWhere('did', $selection)
                    ->first();
            }

            if (!$user) {
                $this->error('無効な選択、またはユーザーが見つかりませんでした。');

                return Command::FAILURE;
            }
        }

        try {
            DB::transaction(function () use ($user) {
                // 現在の管理者ユーザーを検索
                $current_admin = User::where('is_admin', true)->first();

                if ($current_admin && $current_admin->id === $user->id) {
                    $this->info(sprintf('ユーザー [%s] は既に管理者です。', $user->handle));

                    return Command::SUCCESS; // ここでreturnするとトランザクションはコミットされる
                }

                if ($current_admin) {
                    $this->warn(sprintf('現在、ユーザー [%s] が管理者として設定されています。', $current_admin->handle));
                    if (! $this->confirm(sprintf("ユーザー [%s] を新しい管理者として設定し、既存の管理者権限を解除しますか？", $user->handle))) {
                        $this->info('管理者設定をキャンセルしました。');

                        return Command::SUCCESS; // ここでreturnするとトランザクションはコミットされる
                    }

                    // 既存の管理者の権限を解除
                    $current_admin->is_admin = false;
                    $current_admin->save();
                    $this->info(sprintf('ユーザー [%s] の管理者権限を解除しました。', $current_admin->handle));
                }

                // 新しいユーザーを管理者に設定
                $user->is_admin = true;
                $user->save();

                $this->info(sprintf("ユーザー [%s] を管理者に設定しました。", $user->handle));
            });
        } catch (\Exception $e) {
            $this->error(sprintf('管理者設定中にエラーが発生しました: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
