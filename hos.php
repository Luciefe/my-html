<?php

// 示例调用：下载整个 GitHub 仓库并保存到本地
$repo = "Luciefe/ct8-serv00";
$token = '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';

downloadGitHubRepo($repo, '', $token);

// 递归下载 GitHub 仓库中的所有文件并保存到本地目录
function downloadGitHubRepo($repo, $dir = '', $token = '')
{
    $files = fetchGitHubRepoContents($repo, $dir, $token);

    if (is_array($files)) {
        foreach ($files as $item) {
            if ($item['type'] === 'file') {
                $filename = $item['path'];
                $content = fetchGitHubFileContent($repo, $filename, $token);
                saveToFile($filename, $content);
                echo "文件 '$filename' 已成功创建并写入内容。\n";
            } elseif ($item['type'] === 'dir') {
                downloadGitHubRepo($repo, $item['path'], $token);
            }
        }
    } else {
        echo "Error retrieving directory content:\n";
        print_r($files);
    }
}

// 获取 GitHub 仓库指定目录的内容
function fetchGitHubRepoContents($repo, $dir, $token)
{
    $hubUrl = "https://api.github.com/repos/$repo/contents/$dir";
    $headers = [
        'Authorization: token ' . $token,
        'Accept: application/vnd.github.v3+json'
    ];

    $response = sendRequest('GET', $hubUrl, [], $headers);
    return json_decode($response, true);
}

// 获取 GitHub 文件内容
function fetchGitHubFileContent($repo, $name, $token)
{
    $hubUrl = "https://api.github.com/repos/$repo/contents/$name";
    $headers = [
        'Authorization: token ' . $token,
        'Accept: application/vnd.github.v3+json'
    ];

    $response = sendRequest('GET', $hubUrl, [], $headers);
    $data = json_decode($response, true);

    if (isset($data['content'])) {
        return base64_decode($data['content']);
    } else {
        return "Error retrieving file content:\n" . print_r($data, true);
    }
}

// 将内容保存到文件
function saveToFile($filePath, $content)
{
    $localFilePath = __DIR__ . '/' . $filePath;
    $localDirPath = dirname($localFilePath);

    // 创建目录（如果不存在）
    if (!is_dir($localDirPath)) {
        mkdir($localDirPath, 0777, true);
    }

    // 写入内容到文件
    file_put_contents($localFilePath, $content);
}

// 发送 HTTP 请求的函数
function sendRequest($method, $url, $data = [], $headers = [], $json = false, $userAgent = 'PHP Script')
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $method === 'GET' && !empty($data) ? $url . '?' . http_build_query($data) : $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => $userAgent
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json ? json_encode($data) : http_build_query($data));
        $headers[] = 'Content-Type: ' . ($json ? 'application/json' : 'application/x-www-form-urlencoded');
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return 'cURL Error: ' . curl_error($ch);
    }

    curl_close($ch);
    return $response;
}
