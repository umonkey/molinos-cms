<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content" mode="content">
    <h2>Поиск файлов</h2>
    <form method="get" class="advsearch" action="admin/content/files">
      <input type="hidden" name="bare" value="{$bare}" />
      <input type="hidden" name="tinymcepicker" value="{$args/@tinymcepicker}" />
      <table>
        <tbody>
          <tr>
            <th>Название содержит:</th>
            <td>
              <input type="text" class="form-text" name="search" value="{@query}" />
            </td>
          </tr>
          <tr>
            <th>Тип:</th>
            <td>
              <select name="scope">
                <option>любой</option>
                <option value="picture">картинка</option>
                <option value="multimedia">мультимедийный файл</option>
                <option value="office">офисный документ</option>
              </select>
            </td>
          </tr>
          <tr class="okbtn">
            <th/>
            <td>
              <input type="submit" value="Найти" />
            </td>
          </tr>
        </tbody>
      </table>
    </form>
  </xsl:template>
</xsl:stylesheet>
