import { Document, Packer, Paragraph, Table, TableCell, TableRow } from 'docx'
import { saveAs } from 'file-saver'

export function export_json_to_word({
  header,
  data,
  filename,
  title
} = {}) {
  filename = filename || 'document'
  
  // 创建文档
  const doc = new Document({
    sections: [{
      properties: {},
      children: [
        new Paragraph({
          text: title,
          heading: 'Heading1',
          spacing: {
            after: 200
          }
        }),
        new Table({
          rows: [
            // 表头
            new TableRow({
              children: header.map(text => 
                new TableCell({
                  children: [new Paragraph({ text })]
                })
              )
            }),
            // 数据行
            ...data.map(row => 
              new TableRow({
                children: row.map(text => 
                  new TableCell({
                    children: [new Paragraph({ text: String(text) })]
                  })
                )
              })
            )
          ]
        })
      ]
    }]
  })

  // 生成并保存文件
  Packer.toBlob(doc).then(blob => {
    saveAs(blob, `${filename}.docx`)
  })
} 